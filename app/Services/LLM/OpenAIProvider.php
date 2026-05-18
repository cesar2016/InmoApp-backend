<?php

namespace App\Services\LLM;

class OpenAIProvider extends BaseLLMProvider
{
    public function parseContract(string $plainText): array
    {
        $content = $this->callAPI($plainText);

        if ($content === null) {
            throw new \RuntimeException('OpenAI devolvió una respuesta vacía');
        }

        return $this->parseJsonResponse($content);
    }

    public function getName(): string
    {
        return 'openai';
    }
}
