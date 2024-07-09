<?php

namespace App\Services;

use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;

class AIConnector extends Connector
{
    use AcceptsJson, AlwaysThrowOnErrors;

    private string $baseUrl;

    private string $serviceType;

    public function __construct(string $serviceType)
    {
        $this->serviceType = $serviceType;
        $this->baseUrl = config("services.{$serviceType}.base_url");
    }

    /**
     * The Base URL of the API
     */
    public function resolveBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Default headers for every request
     */
    protected function defaultHeaders(): array
    {
        return [
            'Authorization' => 'Bearer '.config("services.{$this->serviceType}.api_key"),
        ];
    }
}
