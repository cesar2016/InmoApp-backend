<?php

namespace App\Services\LLM;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use JsonException;

abstract class BaseLLMProvider implements LLMProviderInterface
{
    protected string $apiKey;

    protected string $baseUrl;

    protected string $model;

    protected int $maxTokens;

    protected float $temperature;

    protected int $timeout;

    protected const SYSTEM_PROMPT = <<<'PROMPT'
Eres un asistente experto legal especializado en el mercado inmobiliario argentino. Tu tarea es extraer información estructurada de contratos de locación (alquiler) de vivienda o comercio.

INSTRUCCIONES CRÍTICAS:
1. **LOCADOR = owner (Dueño/Propietario)**, **LOCATARIO = tenant (Inquilino/Arrendatario)**. No los confundas. En Argentina: "Locador" da en alquiler, "Locatario" recibe en alquiler.
2. Extrae nombres, apellidos y DNI limpios (solo números para DNI).
3. En "property" (inmueble), separa calle y número. Si hay múltiples inmuebles, usa el principal (vivienda). Tipo: "Casa", "Dpto", "Local", "Otro".
4. Para "contract":
   - start_date y end_date en formato ISO (YYYY-MM-DD). Convierte fechas como "1 de Junio de 2026" → "2026-06-01".
   - rent_amount: monto del primer mes como número entero (sin puntos de miles, sin $).
   - increase_frequency_months: cada cuántos meses se ajusta. Palabras clave: "mensual"=1, "bimestral"=2, "trimestral"=3, "cuatrimestral"=4, "semestral"=6, "anual"=12. Si dice "ICL" o "IPC" sin período, asume 4 (cuatrimestral, común en Argentina).
5. "guarantors": Busca fiadores, codeudores solidarios, garantes. Incluye todos con nombre, apellido, DNI, dirección.
6. Si un dato no figura, usa null. No inventes.
7. Devuelve EXCLUSIVAMENTE un objeto JSON válido.

ESTRUCTURA DE RESPUESTA:
{
  "tenant": { "first_name": "...", "last_name": "...", "dni": "...", "address": "...", "whatsapp": null, "email": null },
  "owner": { "first_name": "...", "last_name": "...", "dni": "...", "address": "...", "whatsapp": null, "email": null },
  "property": { "street": "...", "number": "...", "floor": null, "dept": null, "location": "...", "type": "Casa|Dpto|Local|Otro" },
  "contract": { "start_date": "YYYY-MM-DD", "end_date": "YYYY-MM-DD", "rent_amount": 0, "increase_frequency_months": 0 },
  "guarantors": [ { "first_name": "...", "last_name": "...", "dni": "...", "address": "..." } ]
}
PROMPT;

    public function __construct(array $config)
    {
        $this->apiKey = $config['api_key'] ?? '';
        $this->baseUrl = $config['base_url'];
        $this->model = $config['model'];
        $this->maxTokens = $config['max_tokens'] ?? 2000;
        $this->temperature = $config['temperature'] ?? 0.1;
        $this->timeout = $config['request_timeout'] ?? 12;
    }

    public function chat(string $prompt, ?string $systemPrompt = null): string
    {
        $response = Http::connectTimeout(min(5, $this->timeout))
            ->timeout($this->timeout)
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
            ])
            ->post($this->baseUrl.'/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt ?: 'Eres un asistente útil.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => $this->maxTokens,
                'temperature' => $this->temperature,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Error de API '.$this->getName().': '.$response->body());
        }

        $body = $response->json();

        return $body['choices'][0]['message']['content'] ?? '';
    }

    protected function callAPI(string $userText): ?string
    {
        $response = Http::connectTimeout(min(5, $this->timeout))
            ->timeout($this->timeout)
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
            ])
            ->post($this->baseUrl.'/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => self::SYSTEM_PROMPT],
                    ['role' => 'user', 'content' => "Extrae los datos de este contrato:\n\n".$userText],
                ],
                'max_tokens' => $this->maxTokens,
                'temperature' => $this->temperature,
            ]);

        if (! $response->successful()) {
            Log::warning('LLM provider '.$this->getName().' returned status '.$response->status(), [
                'body' => $response->body(),
            ]);

            return null;
        }

        $body = $response->json();

        return $body['choices'][0]['message']['content'] ?? null;
    }

    protected function parseJsonResponse(string $content): array
    {
        $cleaned = trim($content);
        $cleaned = preg_replace('/^```(?:json)?\s*/i', '', $cleaned);
        $cleaned = preg_replace('/\s*```$/', '', $cleaned);
        $cleaned = trim($cleaned);

        if (! str_starts_with($cleaned, '{')) {
            $start = strpos($cleaned, '{');
            $end = strrpos($cleaned, '}');

            if ($start !== false && $end !== false && $end > $start) {
                $cleaned = substr($cleaned, $start, $end - $start + 1);
            }
        }

        try {
            return json_decode($cleaned, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            Log::error($this->getName().': error decodificando JSON', [
                'error' => $e->getMessage(),
                'raw' => $cleaned,
            ]);

            throw new \RuntimeException('Error al decodificar respuesta JSON de '.$this->getName().': '.$e->getMessage());
        }
    }
}
