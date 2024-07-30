<?php

namespace App\Integrations\OpenAI\Requests;

use App\Data\MessageData;
use App\Data\ToolCallData;
use App\Models\Thread;
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
        return '/chat/completions';
    }

    public function defaultBody(): array
    {
        $assistant = $this->thread->project->assistant;

        return [
            'model' => 'lmstudio-community/Meta-Llama-3.1-8B-Instruct-GGUF',
            'messages' => $this->formatMessages($assistant),
            'tools' => array_values($this->tools),
        ];
    }

    private function formatMessages($assistant): array
    {
        return [
            [
                'role' => 'system',
                'content' => $assistant->prompt,
            ],
            ...$this->thread->messages->toArray(),
        ];
    }

    public function createDtoFromResponse(Response $response): MessageData
    {
        $data = $response->json();
        $message = $data['choices'][0]['message'] ?? [];
        $tools = collect([]);
        if (isset($message['tool_calls'])) {
            foreach ($message['tool_calls'] as $toolCall) {
                $tools->push(ToolCallData::from($toolCall));
            }

            $message['tool_calls'] = $tools;
        }

        return MessageData::from($message ?? []);
    }
}
