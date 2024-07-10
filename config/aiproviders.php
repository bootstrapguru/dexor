<?php

return [
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'models' => [
            'gpt-4o',
            'gpt-3.5-turbo',
            'gpt-4-turbo',
            'gpt-4-turbo-preview',
        ],
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
        'listModelsRequest' => \App\Integrations\Claude\Requests\ListModelsRequest::class,
        'chatRequest' => \App\Integrations\Claude\Requests\ChatRequest::class,
    ],

    'ollama' => [
        'connector' => \App\Integrations\Ollama\OllamaConnector::class,
        'listModelsRequest' => \App\Integrations\Ollama\Requests\ListModelsRequest::class,
        'chatRequest' => \App\Integrations\Ollama\Requests\ChatRequest::class,
    ],
];
