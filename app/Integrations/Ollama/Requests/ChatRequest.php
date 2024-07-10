<?php

namespace App\Integrations\Ollama\Requests;

use App\Data\MessageData;
use App\Models\Thread;
use Illuminate\Support\Collection;
use JsonException;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

class ChatRequest extends Request implements HasBody
{
    use HasJsonBody;

    /**
     * The HTTP method
     */
    protected Method $method = Method::POST;

    public function __construct(
        public Thread $thread,
        public array $tools
    ) {}

    /**
     * The endpoint
     */
    public function resolveEndpoint(): string
    {
        return '/chat';
    }

    /**
     * Data to be sent in the body of the request
     */
    public function defaultBody(): array
    {
        $assistant = $this->thread->project->assistant;

        $messages = [[
            'role' => 'system',
            'content' => $assistant->prompt,
        ],
            ...$this->thread->messages,
        ];

        return [
            'model' => $assistant->model,
            'messages' => $messages,
            'stream'  => false,
            'tools' => array_values($this->tools),
        ];
    }


    /**
     * @throws JsonException
     */
    public function createDtoFromResponse(Response $response): MessageData
    {
        $data = $response->json();
        return MessageData::from($data['message']);
    }
}
