<?php

namespace App\Services\LLM;

use Illuminate\Support\Facades\Http;

class GroqProvider extends BaseLLMProvider
{
    public function parseContract(string $plainText): array
    {
        $response = Http::connectTimeout(5)
            ->timeout(45) // More time for large contracts
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
            ])
            ->post($this->baseUrl.'/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => self::SYSTEM_PROMPT."\nIMPORTANTE: Responde ÚNICAMENTE con un objeto JSON válido."],
                    ['role' => 'user', 'content' => "Extrae los datos de este contrato en formato JSON:\n\n".$plainText],
                ],
                'max_tokens' => $this->maxTokens,
                'temperature' => 0.0, // Zero for maximum precision
                'response_format' => ['type' => 'json_object'],
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Error de API Groq: '.$response->body());
        }

        $body = $response->json();
        $content = $body['choices'][0]['message']['content'] ?? null;

        if ($content === null || trim($content) === '') {
            throw new \RuntimeException('Groq devolvió una respuesta vacía');
        }

        return $this->parseJsonResponse($content);
    }

    public function getName(): string
    {
        return 'groq';
    }
}
