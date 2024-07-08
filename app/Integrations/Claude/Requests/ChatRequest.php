<?php

namespace App\Integrations\Claude\Requests;

use App\Models\Thread;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
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

        $messages = $this->thread->messages->map(function ($message){
           return [
               'role' => $message->role,
               'content' => $message->content
           ];
        });

        return [
            'model' => $this->thread->assistant->model,
            'messages' => $messages,
            'system' => $this->thread->assistant->prompt,
            'tools' => array_values($tools),
            'max_tokens' => 100
        ];
    }
}
