<?php

return [
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'base_url' => 'https://api.openai.com/v1',
        'models' => [
            'gpt-3.5-turbo',
            'gpt-4-turbo',
            'gpt-4-turbo-preview',
        ],
    ],

    'claude' => [
        'api_key' => env('CLAUDE_API_KEY'),
        'base_url' => 'https://api.claude.com/v1',
        'models' => [
            'claude-1',
            'claude-2',
            'claude-instant',
        ],
    ],
];
