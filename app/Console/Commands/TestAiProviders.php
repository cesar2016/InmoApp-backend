<?php

namespace App\Console\Commands;

use App\Services\LLM\GroqProvider;
use App\Services\LLM\MistralProvider;
use App\Services\LLM\OllamaProvider;
use App\Services\LLM\OpenAIProvider;
use App\Services\LLM\xAIProvider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestAiProviders extends Command
{
    protected $signature = 'ai:test';

    protected $description = 'Test all enabled AI providers';

    public function handle()
    {
        $this->info('Testing AI Providers...');

        $providers = config('ai-parser.providers', []);
        $enabledProviders = config('ai-parser.enabled_providers', []);

        $classes = [
            'openai' => OpenAIProvider::class,
            'groq' => GroqProvider::class,
            'mistral' => MistralProvider::class,
            'ollama' => OllamaProvider::class,
            'xai' => xAIProvider::class,
        ];

        foreach ($providers as $cfg) {
            $name = $cfg['name'];

            if (! empty($enabledProviders) && ! in_array($name, $enabledProviders, true)) {
                $this->line("<fg=yellow>[SKIP]</> {$name} (not enabled)");

                continue;
            }

            if (empty($cfg['api_key'])) {
                $this->line("<fg=red>[FAIL]</> {$name} (no API key)");

                continue;
            }

            $class = $classes[$name] ?? null;
            if (! $class) {
                $this->line("<fg=red>[ERROR]</> {$name} (class not found)");

                continue;
            }

            $this->info("Executing test for: {$name} (Model: {$cfg['model']})...");

            try {
                $provider = new $class($cfg);
                $startTime = microtime(true);

                // Simple prompt to test if it's working and coherent
                $result = $provider->parseContract('Este es un contrato de prueba entre Juan Perez (Locatario) y Maria Garcia (Locadora). Propiedad en Calle Falsa 123. Alquiler $100.000 mensuales.');

                $duration = round(microtime(true) - $startTime, 2);

                $this->line("<fg=green>[OK]</> {$name} in {$duration}s");
                $this->info('Response summary: Tenant: '.($result['tenant']['first_name'] ?? 'N/A').' '.($result['tenant']['last_name'] ?? 'N/A').' | Owner: '.($result['owner']['first_name'] ?? 'N/A').' '.($result['owner']['last_name'] ?? 'N/A'));

            } catch (\Throwable $e) {
                $this->line("<fg=red>[FAIL]</> {$name} error: ".$e->getMessage());
                // Log::error("AI Test Fail: " . $name, ['error' => $e->getMessage()]);
            }
        }
    }
}
