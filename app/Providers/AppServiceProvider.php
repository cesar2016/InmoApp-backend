<?php

namespace App\Providers;

use App\Services\AiContractParserService;
use App\Services\LLM\GroqProvider;
use App\Services\LLM\MistralProvider;
use App\Services\LLM\OllamaProvider;
use App\Services\LLM\OpenAIProvider;
use App\Services\LLM\xAIProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AiContractParserService::class, function ($app) {
            $providers = [];
            $config = config('ai-parser.providers', []);
            $enabledProviders = config('ai-parser.enabled_providers', []);

            $classes = [
                'openai' => OpenAIProvider::class,
                'groq' => GroqProvider::class,
                'mistral' => MistralProvider::class,
                'ollama' => OllamaProvider::class,
                'xai' => xAIProvider::class,
            ];

            foreach ($config as $cfg) {
                if (! empty($enabledProviders) && ! in_array($cfg['name'], $enabledProviders, true)) {
                    continue;
                }

                $class = $classes[$cfg['name']] ?? null;
                if ($class && ! empty($cfg['api_key'])) {
                    $providers[] = new $class($cfg);
                }
            }

            return new AiContractParserService(...$providers);
        });
    }

    public function boot(): void
    {
        //
    }
}
