<?php

namespace App\Integrations\Ollama\Requests;

use App\Data\MessageData;
use App\Data\ToolCallData;
use App\Data\ToolFunctionData;
use App\Models\Thread;
use Illuminate\Support\Str;
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

        $body = [
            'model' => $assistant->model,
            'messages' => $this->formatMessages($assistant),
            'stream' => false,
            'raw' => true,
            'tools' => $this->formatTools(),
        ];


        return $body;
    }

    private function formatMessages($assistant): array
    {
        $systemMessage = [
            'role' => 'system',
            'content' => $assistant->prompt,
        ];

        $formattedMessages = [$systemMessage];

        foreach ($this->thread->messages as $message) {
            $formattedMessage = [
                'role' => $message['role'],
                'content' => $message['content'],
            ];

            if (!empty($message['tool_calls'])) {
                $formattedMessage['tool_calls'] = $this->formatToolCalls($message['tool_calls']);
            }

            $formattedMessages[] = $formattedMessage;
        }

        return $formattedMessages;
    }

    private function formatToolCalls($toolCalls): array
    {
        return array_map(function ($toolCall) {
            $function = $toolCall['function'];

            // Ensure arguments is a JSON object string
            $arguments = is_string($function['arguments'])
                ? json_decode($function['arguments'], true) // Decode string to ensure it's valid JSON
                : json_encode($function['arguments'], JSON_FORCE_OBJECT); // Encode array/object to JSON object

            return [
                'id' => $toolCall['id'],
                'type' => 'function',
                'function' => [
                    'name' => $function['name'],
                    'arguments' => $arguments, // Always a JSON object
                ],
            ];
        }, $toolCalls);
    }

    private function formatTools(): array
    {

        $formattedTools = [];
        foreach ($this->tools as $key => $tool) {
            if (is_array($tool) && isset($tool['function'])) {
                $formattedTools[] = [
                    'type' => 'function',
                    'function' => [
                        'name' => $tool['function']['name'] ?? $key,
                        'description' => $tool['function']['description'] ?? '',
                        'parameters' => $tool['function']['parameters'] ?? [],
                    ],
                ];
            } else {
            }
        }


        return $formattedTools;
    }

    /**
     * @throws JsonException
     */
    public function createDtoFromResponse(Response $response): MessageData
    {
        $data = $response->json();
        $message = $data['message'] ?? [];
        $tools = collect();

        if (isset($message['tool_calls'])) {
            foreach ($message['tool_calls'] as $toolCall) {
                $arguments = $toolCall['function']['arguments'];
                // Ensure arguments is a JSON string
                if (is_array($arguments)) {
                    $arguments = json_encode($arguments, JSON_FORCE_OBJECT);
                } elseif (!is_string($arguments)) {
                    $arguments = json_encode([$arguments], JSON_FORCE_OBJECT);
                }

                $fn = ToolFunctionData::from([
                    'name' => $toolCall['function']['name'],
                    'arguments' => $arguments
                ]);

                $tools->push(ToolCallData::from([
                    'id' => $toolCall['id'] ?? 'ollama-'.Str::random(10),
                    'type' => 'function',
                    'function' => $fn
                ]));
            }

            $message['tool_calls'] = $tools;
        }

        return MessageData::from($message);
    }
}
