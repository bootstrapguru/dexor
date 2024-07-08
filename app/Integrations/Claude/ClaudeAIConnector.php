<?php

namespace App\Integrations\Claude;

use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;

class ClaudeAIConnector extends Connector
{
    use AcceptsJson, AlwaysThrowOnErrors;

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
            'x-api-key' => config("aiproviders.claude.api_key"),
            'anthropic-version' => '2023-06-01',
        ];
    }
}
