<?php

return [
    'enabled_providers' => array_values(array_filter(array_map('trim', explode(',', env('AI_ENABLED_PROVIDERS', 'groq,mistral,ollama'))))),
    'remote_provider_limit' => (int) env('AI_REMOTE_PROVIDER_LIMIT', 2),
    'min_remote_text_length' => (int) env('AI_MIN_REMOTE_TEXT_LENGTH', 120),

    'providers' => [
        [
            'name' => 'mistral',
            'api_key' => env('AI_MISTRAL_API_KEY'),
            'base_url' => 'https://api.mistral.ai/v1',
            'model' => env('AI_MISTRAL_MODEL', 'mistral-small-latest'),
        ],
        [
            'name' => 'ollama',
            'api_key' => env('AI_OLLAMA_API_KEY'),
            'base_url' => env('AI_OLLAMA_BASE_URL', 'https://ollama.com/api'),
            'model' => env('AI_OLLAMA_MODEL', 'gpt-oss:120b'),
        ],
        [
            'name' => 'groq',
            'api_key' => env('AI_GROQ_API_KEY'),
            'base_url' => 'https://api.groq.com/openai/v1',
            'model' => env('AI_GROQ_MODEL', 'llama-3.3-70b-versatile'),
        ],
        [
            'name' => 'openai',
            'api_key' => env('AI_OPENAI_API_KEY'),
            'base_url' => 'https://api.openai.com/v1',
            'model' => env('AI_OPENAI_MODEL', 'gpt-4o-mini'),
        ],
        [
            'name' => 'xai',
            'api_key' => env('AI_XAI_API_KEY'),
            'base_url' => 'https://api.x.ai/v1',
            'model' => env('AI_XAI_MODEL', 'grok-2-latest'),
        ],
    ],

    'max_tokens' => 3000,
    'temperature' => 0.1,
    'request_timeout' => 30, // Increased timeout for Chat Sandbox
    'pdf_text_timeout' => 15,
];
