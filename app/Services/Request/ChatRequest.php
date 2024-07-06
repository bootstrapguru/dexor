<?php

namespace App\Services\Request;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class ChatRequest extends Request implements HasBody
{
    use HasJsonBody;

    /**
     * The HTTP method
     *
     * @var Method
     */
    protected Method $method = Method::POST;

    /**
     * The endpoint
     *
     * @return string
     */
    public function resolveEndpoint(): string
    {
        return '/chat/completions';
    }

    /**
     * Default headers for the request
     *
     * @return array
     */
    protected function defaultHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Data to be sent in the body of the request
     *
     * @return array
     */
    public function defaultBody(): array
    {
        return [
            'model' => 'gpt-3.5-turbo',
            'prompt' => $this->prompt,
            'max_tokens' => 100,
            'temperature' => 0.7,
        ];
    }

    public function __construct(private readonly string $prompt)
    {
    }
}
