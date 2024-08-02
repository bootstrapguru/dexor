<?php

namespace App\Integrations\OpenAI;

use App\Models\Assistant;
use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;
use Saloon\Traits\Plugins\HasTimeout;

class OpenAIConnector extends Connector
{
    use AcceptsJson, AlwaysThrowOnErrors, HasTimeout;

    protected int $connectTimeout = 60;

    protected int $requestTimeout = 120;

    public function __construct(protected readonly string $service) {
        //
    }

    /**
     * The Base URL of the API
     */
    public function resolveBaseUrl(): string
    {
        return match ($this->service) {
            'openai' => 'https://api.openai.com/v1',
            'deep_seek' => 'https://api.deepseek.com/v1',
        };

    }

    /**
     * Default headers for every request
     */
    protected function defaultHeaders(): array
    {
        return [
            'Authorization' => 'Bearer '.config("aiproviders.{$this->service}.api_key"),
        ];
    }
}
