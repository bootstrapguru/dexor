<?php

namespace App\Integrations\Ollama\Requests;

use App\Data\MessageData;
use App\Models\Thread;
use JsonException;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

class ChatRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        public readonly Thread $thread,
        public readonly array $tools
    ) {}

    public function resolveEndpoint(): string
    {
        return '/chat';
    }

    public function defaultBody(): array
    {
        $assistant = $this->thread->project->assistant;

        return [
            'model' => $assistant->model,
            'messages' => $this->formatMessages($assistant),
            'stream' => false,
            'raw' => true,
            'tools' => array_values($this->tools),
        ];
    }

    private function formatMessages($assistant): array
    {
        $systemMessage = [
            'role' => 'system',
            'content' => sprintf(
                '[INST]%s[/INST] [AVAILABLE_TOOLS]%s[/AVAILABLE_TOOLS]',
                $assistant->prompt,
                json_encode(array_values($this->tools))
            ),
        ];

        return [$systemMessage, ...$this->thread->messages->toArray()];
    }

    /**
     * @throws JsonException
     */
    public function createDtoFromResponse(Response $response): MessageData
    {
        $data = $response->json();
        return MessageData::from($data['message'] ?? []);
    }
}