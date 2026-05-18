<?php

namespace App\Services\LLM;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaProvider extends BaseLLMProvider
{
    public function parseContract(string $plainText): array
    {
        $content = $this->callOllamaAPI($plainText);

        if ($content === null) {
            throw new \RuntimeException('Ollama devolvió una respuesta vacía');
        }

        return $this->parseJsonResponse($content);
    }

    public function getName(): string
    {
        return 'ollama';
    }

    private function callOllamaAPI(string $userText): ?string
    {
        $response = Http::connectTimeout(min(5, $this->timeout))
            ->timeout($this->timeout)
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
            ])
            ->post($this->baseUrl.'/chat', [
                'model' => $this->model,
                'stream' => false,
                'messages' => [
                    ['role' => 'system', 'content' => self::SYSTEM_PROMPT],
                    ['role' => 'user', 'content' => "Extrae los datos de este contrato:\n\n".$userText],
                ],
                'options' => [
                    'temperature' => $this->temperature,
                    'num_predict' => $this->maxTokens,
                ],
            ]);

        if (! $response->successful()) {
            Log::warning('LLM provider '.$this->getName().' returned status '.$response->status(), [
                'body' => $response->body(),
            ]);

            return null;
        }

        $body = $response->json();

        return $body['message']['content'] ?? null;
    }
}
