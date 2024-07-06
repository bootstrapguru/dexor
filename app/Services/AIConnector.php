<?php

namespace App\Services;

use Saloon\Http\Connector;
use Saloon\Http\Response;
use Saloon\Traits\Plugins\AcceptsJson;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;

class AIConnector extends Connector
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
            'Accept' => 'application/vnd.Nexhealth+json; version=2',
            'Authorization' => 'Bearer ' . config('droid.api_key'),
        ];
    }

    /**
     * Default HTTP client options
     */
    protected function defaultConfig(): array
    {
        return [];
    }
}
