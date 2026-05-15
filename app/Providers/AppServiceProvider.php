<?php

namespace App\Providers;

use App\Services\AiContractParserService;
use App\Services\LLM\GroqProvider;
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

            $classes = [
                'openai' => OpenAIProvider::class,
                'groq'   => GroqProvider::class,
                'xai'    => xAIProvider::class,
            ];

            foreach ($config as $cfg) {
                $class = $classes[$cfg['name']] ?? null;
                if ($class && !empty($cfg['api_key'])) {
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
