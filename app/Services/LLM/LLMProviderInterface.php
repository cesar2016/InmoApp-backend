<?php

namespace App\Services\LLM;

interface LLMProviderInterface
{
    public function parseContract(string $plainText): array;

    public function chat(string $prompt, ?string $systemPrompt = null): string;

    public function getName(): string;
}
