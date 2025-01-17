<?php

return [
    'openrouter' => [
        'api_key' => env('OPENROUTER_API_KEY'),
        'connector' => \App\Integrations\OpenRouter\OpenRouterConnector::class,
        'listModelsRequest' => \App\Integrations\OpenRouter\Requests\ListModelsRequest::class,
        'chatRequest' => \App\Integrations\OpenRouter\Requests\ChatRequest::class,
    ],
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'connector' => \App\Integrations\OpenAI\OpenAIConnector::class,
        'listModelsRequest' => \App\Integrations\OpenAI\Requests\ListModelsRequest::class,
        'chatRequest' => \App\Integrations\OpenAI\Requests\ChatRequest::class,
    ],

    'deep_seek' => [
        'api_key' => env('DEEP_SEEK_API_KEY'),
        'connector' => \App\Integrations\OpenAI\OpenAIConnector::class,
        'listModelsRequest' => \App\Integrations\OpenAI\Requests\ListModelsRequest::class,
        'chatRequest' => \App\Integrations\OpenAI\Requests\ChatRequest::class,
    ],

    'claude' => [
        'api_key' => env('CLAUDE_API_KEY'),
        'models' => [
            'claude-3-5-sonnet-20240620',
            'claude-3-opus-20240229',
            'claude-3-sonnet-20240229',
        ],
        'connector' => \App\Integrations\Claude\ClaudeAIConnector::class,
        'chatRequest' => \App\Integrations\Claude\Requests\ChatRequest::class,
    ],

    'ollama' => [
        'connector' => \App\Integrations\Ollama\OllamaConnector::class,
        'listModelsRequest' => \App\Integrations\Ollama\Requests\ListModelsRequest::class,
        'chatRequest' => \App\Integrations\Ollama\Requests\ChatRequest::class,
    ],
];
