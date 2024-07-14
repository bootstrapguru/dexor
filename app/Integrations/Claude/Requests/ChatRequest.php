<?php

namespace App\Integrations\Claude\Requests;

use App\Data\MessageData;
use App\Data\ToolCallData;
use App\Data\ToolFunctionData;
use App\Models\Thread;
use Illuminate\Support\Collection;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;
use Saloon\Traits\Plugins\HasTimeout;

class ChatRequest extends Request implements HasBody
{
    use HasJsonBody;

    /**
     * The HTTP method
     */
    protected Method $method = Method::POST;

    public function __construct(
        public readonly Thread $thread,
        public readonly array $tools
    ) {}

    /**
     * The endpoint
     */
    public function resolveEndpoint(): string
    {
        return '/messages';
    }

    /**
     * Data to be sent in the body of the request
     */
    public function defaultBody(): array
    {
        return [
            'model' => $this->thread->assistant->model,
            'messages' => $this->formatMessages(),
            'system' => $this->thread->assistant->prompt,
            'tools' => $this->formatTools(),
            'max_tokens' => 4096,
        ];
    }

    private function formatTools(): array
    {
        return array_values(array_map(function ($tool) {
            $claudeTool = $tool['function'];
            $claudeTool['input_schema'] = $claudeTool['parameters'];
            unset($claudeTool['parameters']);
            return $claudeTool;
        }, $this->tools));
    }

    private function formatMessages(): array
    {
        return $this->thread->messages->map(function ($message) {
            return match ($message->role) {
                'assistant' => $this->formatAssistantMessage($message),
                'tool' => $this->formatToolMessage($message),
                default => [
                    'role' => $message->role,
                    'content' => $message->content,
                ],
            };
        })->toArray();
    }

    private function formatAssistantMessage($message): array
    {
        $content = [['type' => 'text', 'text' => $message->content]];

        if ($message->tool_calls !== null) {
            $content = array_merge($content, array_map(function ($toolCall) {
                return [
                    'type' => $toolCall['type'],
                    'id' => $toolCall['id'],
                    'name' => $toolCall['function']['name'],
                    'input' => json_decode($toolCall['function']['arguments'], true),
                ];
            }, $message->tool_calls));
        }

        return [
            'role' => 'assistant',
            'content' => $content,
        ];
    }

    private function formatToolMessage($message): array
    {
        $content = [
            [
                'type' => 'tool_result',
                'tool_use_id' => $message->tool_call_id,
            ]
        ];

        if ($message->content) {
            $content[0]['content'] = $message->content;
        }

        return [
            'role' => 'user',
            'content' => $content,
        ];
    }

    public function createDtoFromResponse(Response $response): MessageData
    {
        $data = $response->json();
        $message = null;
        $tools = new Collection();

        foreach ($data['content'] as $choice) {
            if ($choice['type'] === 'text') {
                $message = MessageData::from([
                    'role' => 'assistant',
                    'content' => $choice['text']
                ]);
            } else {
                $tools->push(ToolCallData::from([
                    'id' => $choice['id'],
                    'type' => $choice['type'],
                    'function' => ToolFunctionData::from([
                        'name' => $choice['name'],
                        'arguments' => json_encode($choice['input'])
                    ])
                ]));
            }
        }

        $message->tool_calls = $tools;

        return $message;
    }
}
