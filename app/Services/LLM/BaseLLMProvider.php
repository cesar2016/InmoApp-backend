<?php

namespace App\Services\LLM;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class BaseLLMProvider implements LLMProviderInterface
{
    protected string $apiKey;
    protected string $baseUrl;
    protected string $model;
    protected int $maxTokens;
    protected float $temperature;
    protected int $timeout;

    protected const SYSTEM_PROMPT = <<<PROMPT
Eres un asistente experto en extraer datos de contratos de alquiler de Argentina.
Analiza el texto del contrato y extrae la información en formato JSON.
Devuelve SOLO el JSON sin explicaciones ni markdown.

El JSON debe seguir esta estructura exacta:
{
  "tenant": {
    "first_name": "...",
    "last_name": "...",
    "dni": "...",
    "address": "...",
    "whatsapp": "...",
    "email": "..."
  },
  "owner": {
    "first_name": "...",
    "last_name": "...",
    "dni": "...",
    "address": "...",
    "whatsapp": "...",
    "email": "..."
  },
  "property": {
    "street": "...",
    "number": "...",
    "floor": "...",
    "dept": "...",
    "location": "...",
    "type": "..."
  },
  "contract": {
    "start_date": "...",
    "end_date": "...",
    "rent_amount": 0,
    "increase_frequency_months": 0
  },
  "guarantors": [
    {
      "first_name": "...",
      "last_name": "...",
      "dni": "...",
      "address": "...",
      "whatsapp": "...",
      "email": "..."
    }
  ]
}

Reglas:
- LOCATARIO/LOCATARIA = tenant (inquilino)
- LOCADOR/LOCADORA = owner (propietario)
- Fecha inicio: "desde el día X de mes de año"
- Fecha fin: "hasta el día X de mes de año"
- Formato fechas: YYYY-MM-DD
- rent_amount: solo el número, sin puntos, sin símbolos
- increase_frequency_months: 3 (trimestral), 4 (cuatrimestral), 6 (semestral), 12 (anual)
- type: Casa, Dpto, Local, Otro, PH, Lote, Galpón
- Si un campo no se encuentra, devolver null
- Los guarantors pueden ser 0 o más, siempre como array
- whatsapp: solo dígitos sin espacios ni guiones
PROMPT;

    public function __construct(array $config)
    {
        $this->apiKey = $config['api_key'];
        $this->baseUrl = $config['base_url'];
        $this->model = $config['model'];
        $this->maxTokens = $config['max_tokens'] ?? 2000;
        $this->temperature = $config['temperature'] ?? 0.1;
        $this->timeout = $config['request_timeout'] ?? 30;
    }

    protected function callAPI(string $userText): ?string
    {
        $response = Http::timeout($this->timeout)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->post($this->baseUrl . '/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => self::SYSTEM_PROMPT],
                    ['role' => 'user', 'content' => "Extrae los datos de este contrato:\n\n" . $userText],
                ],
                'max_tokens' => $this->maxTokens,
                'temperature' => $this->temperature,
            ]);

        if (!$response->successful()) {
            Log::warning('LLM provider ' . $this->getName() . ' returned status ' . $response->status(), [
                'body' => $response->body(),
            ]);
            return null;
        }

        $body = $response->json();

        return $body['choices'][0]['message']['content'] ?? null;
    }
}
