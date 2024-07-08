<?php

namespace App\Integrations\Ollama\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class ChatRequest extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/api/chat';
    }
}
