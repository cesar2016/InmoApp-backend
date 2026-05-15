<?php

namespace App\Services\LLM;

use Illuminate\Support\Facades\Log;

class xAIProvider extends BaseLLMProvider
{
    public function parseContract(string $plainText): array
    {
        $content = $this->callAPI($plainText);

        if ($content === null) {
            throw new \RuntimeException('xAI devolvió una respuesta vacía');
        }

        return $this->parseResponse($content);
    }

    public function getName(): string
    {
        return 'xai';
    }

    private function parseResponse(string $content): array
    {
        $cleaned = trim($content);
        $cleaned = preg_replace('/^```(?:json)?\s*/i', '', $cleaned);
        $cleaned = preg_replace('/\s*```$/', '', $cleaned);
        $cleaned = trim($cleaned);

        $decoded = json_decode($cleaned, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('xAI: error decodificando JSON', [
                'error' => json_last_error_msg(),
                'raw' => $cleaned,
            ]);
            throw new \RuntimeException('Error al decodificar respuesta JSON de xAI: ' . json_last_error_msg());
        }

        return $decoded;
    }
}
