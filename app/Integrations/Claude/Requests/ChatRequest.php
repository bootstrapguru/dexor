<?php

namespace App\Integrations\Claude\Requests;

use App\Data\MessageData;
use App\Data\ToolCallData;
use App\Data\ToolFunctionData;
use App\Models\Thread;
use Illuminate\Support\Facades\Log;
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
        return '/messages';
    }

    /**
     * Data to be sent in the body of the request
     */
    public function defaultBody(): array
    {
        $tools = collect($this->tools)->map(function ($tool) {
            $claudeTool = $tool['function'];
            $claudeTool['input_schema'] = $claudeTool['parameters'];
            // unset parameters
            unset($claudeTool['parameters']);

            return $claudeTool;
        })->toArray();

        $messages = $this->thread->messages->map(function ($message) {

            $modifiedMessage = [
                'role' => $message->role,
                'content' => $message->content,
            ];

            if ($message->role === 'assistant') {
                $modifiedMessage = [
                    'role' => $message->role,
                    'content' => [[
                        'type' => 'text',
                        'text' => $message->content
                    ]]
                ];

                if ($message->tool_calls !== null) {
                    var_dump($message->tool_calls);

                    foreach ($message->tool_calls as $toolCall) {

                        $modifiedMessage['content'][] = [
                            'type' => $toolCall['type'],
                            'id' => $toolCall['id'],
                            'name' => $toolCall['function']['name'],
                            'input' => json_decode($toolCall['function']['arguments'])
                        ];
                    }
                }
            }

            if ($message->role === 'tool') {
                // for claude, the tool response has different structure
                $modifiedMessage =  [
                    'role' => 'user',
                    'content' => [[
                        'type' => 'tool_result',
                        "tool_use_id"=> $message->tool_call_id,
                    ]]
                ];

                if ($message->content) {
                    $modifiedMessage['content'][0]['content'] = $message->content;
                }
            }

            return $modifiedMessage;
        });

        var_dump($messages);

        return [
            'model' => $this->thread->assistant->model,
            'messages' => $messages,
            'system' => $this->thread->assistant->prompt,
            'tools' => array_values($tools),
            'max_tokens' => 500,
        ];
    }

    private function getToolResults($messages) {
//        {
//            "type": "tool_result",
//            "tool_use_id": "toolu_01A09q90qw90lq917835lq9",
//            "content": "15 degrees"
//        }

        $toolResults = [];

        foreach ($messages as $message) {
            if ($message)
            $toolResults[] = [
                'type' => 'tool_result',
                'too_use_id' => $message->tool_call_id,
            ];
        }




    }

    public function createDtoFromResponse(Response $response): MessageData
    {
        $data = $response->json();
        $message = null;
        $tools = collect([]);
        foreach ($data['content'] as $choice) {
            if ($choice['type'] === 'text') {
                $message = MessageData::from([
                    'role' => 'assistant',
                    'content' => $choice['text']
                ]);
            }
            else {
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
