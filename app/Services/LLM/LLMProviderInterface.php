<?php

namespace App\Services\LLM;

interface LLMProviderInterface
{
    public function parseContract(string $plainText): array;

    public function getName(): string;
}
