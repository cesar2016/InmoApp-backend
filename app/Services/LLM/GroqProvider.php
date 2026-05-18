<?php

namespace App\Services\LLM;

class GroqProvider extends BaseLLMProvider
{
    public function parseContract(string $plainText): array
    {
        $content = $this->callAPI($plainText);

        if ($content === null) {
            throw new \RuntimeException('Groq devolvió una respuesta vacía');
        }

        return $this->parseJsonResponse($content);
    }

    public function getName(): string
    {
        return 'groq';
    }
}
