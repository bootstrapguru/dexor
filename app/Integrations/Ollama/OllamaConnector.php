<?php

namespace App\Integrations\Ollama;

use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;
use Saloon\Traits\Plugins\HasTimeout;

class OllamaConnector extends Connector
{
    use AcceptsJson, AlwaysThrowOnErrors, HasTimeout;

    protected int $connectTimeout = 60;

    protected int $requestTimeout = 120;

    /**
     * The Base URL of the API
     */
    public function resolveBaseUrl(): string
    {
        return 'http://localhost:11434/api';
    }
}
