<?php

namespace App\Integrations\OpenAI;

use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;

class OpenAIConnector extends Connector
{
    use AcceptsJson, AlwaysThrowOnErrors;

    /**
     * The Base URL of the API
     */
    public function resolveBaseUrl(): string
    {
        return 'https://api.openai.com/v1';
    }

    /**
     * Default headers for every request
     */
    protected function defaultHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . config("aiproviders.openai.api_key"),
        ];
    }
}
