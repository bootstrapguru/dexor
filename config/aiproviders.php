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
    ],

    'claude' => [
        'api_key' => env('CLAUDE_API_KEY'),
        'models' => [
            'claude-3-5-sonnet-20240620',
            'claude-3-opus-20240229',
            'claude-3-sonnet-20240229	',
        ],
    ],

    'ollama' => []
];
