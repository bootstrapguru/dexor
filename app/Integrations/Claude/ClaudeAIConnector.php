<?php

namespace App\Integrations\Claude;

use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;
use Saloon\Traits\Plugins\HasTimeout;

class ClaudeAIConnector extends Connector
{
    use AcceptsJson, AlwaysThrowOnErrors, HasTimeout;

    protected int $connectTimeout = 60;

    protected int $requestTimeout = 120;

    /**
     * The Base URL of the API
     */
    public function resolveBaseUrl(): string
    {
        return 'https://api.anthropic.com/v1';
    }

    /**
     * Default headers for every request
     */
    protected function defaultHeaders(): array
    {
        return [
            'x-api-key' => config('aiproviders.claude.api_key'),
            'anthropic-version' => '2023-06-01',
            'anthropic-beta' => 'prompt-caching-2024-07-31',
        ];
    }
}
