<?php

return [
    'providers' => [
        [
            'name' => 'openai',
            'api_key' => env('AI_OPENAI_API_KEY'),
            'base_url' => 'https://api.openai.com/v1',
            'model' => env('AI_OPENAI_MODEL', 'gpt-4o-mini'),
        ],
        [
            'name' => 'groq',
            'api_key' => env('AI_GROQ_API_KEY'),
            'base_url' => 'https://api.groq.com/openai/v1',
            'model' => env('AI_GROQ_MODEL', 'llama-3.3-70b-versatile'),
        ],
        [
            'name' => 'xai',
            'api_key' => env('AI_XAI_API_KEY'),
            'base_url' => 'https://api.x.ai/v1',
            'model' => env('AI_XAI_MODEL', 'grok-2-latest'),
        ],
    ],

    'max_tokens' => 2000,
    'temperature' => 0.1,
    'request_timeout' => 12,
];
