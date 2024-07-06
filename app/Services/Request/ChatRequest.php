<?php

namespace App\Services\Request;

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
     *
     * @var Method
     */
    protected Method $method = Method::POST;

    public function __construct(
        public Thread $thread,
        public array $tools
    ) {
    }

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
     * Data to be sent in the body of the request
     *
     * @return array
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
            'tools' => array_values($this->tools)
        ];
    }
}
