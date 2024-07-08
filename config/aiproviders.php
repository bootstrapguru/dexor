<?php

return [
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'base_url' => 'https://api.openai.com/v1',
        'models' => [
            'gpt-4o',
            'gpt-3.5-turbo',
            'gpt-4-turbo',
            'gpt-4-turbo-preview',
        ],
    ],

    'claude' => [
        'api_key' => env('CLAUDE_API_KEY'),
        'base_url' => 'https://api.claude.com/v1',
        'models' => [
            'claude-3-5-sonnet-20240620',
            'claude-3-opus-20240229',
            'claude-3-sonnet-20240229	',
        ],
    ],
];
