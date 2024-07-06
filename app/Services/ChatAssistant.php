<?php

namespace App\Services;

use App\Tools\ListFiles;
use App\Tools\ReadFile;
use App\Tools\UpdateFile;
use App\Tools\WriteToFile;
use App\Traits\HasTools;
use Exception;
use OpenAI;
use OpenAI\Client;
use OpenAI\Responses\Threads\Runs\ThreadRunResponse;

use function Laravel\Prompts\spin;
use function Termwind\render;

class ChatAssistant
{
    use HasTools;

    private Client $client;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->client = OpenAI::client(config('droid.api_key'));

        // register the tools
        $this->register([
            WriteToFile::class,
            UpdateFile::class,
            ListFiles::class,
            ReadFile::class,
        ]);

    }

    public function createAssistant()
    {
        return $this->client->assistants()->create([
            'name' => 'Droid Dev',
            'model' => config('droid.model'),
            'description' => 'Droid Dev is a code generation assistant for Web applications',
            'instructions' => config('droid.prompt'),
            'tools' => array_values($this->registered_tools),
        ]);

    }

    public function createThread()
    {
        return spin(
            fn () => $this->client->threads()->create([
                'messages' => [
                    [
                        'role' => 'assistant',
                        'content' => 'The base path for this project is '.getcwd(),
                    ],
                ],
            ]),
            'Creating New Thread...'
        );
    }

    /**
     * @throws \ReflectionException
     */
    public function getAnswer($thread, $message): string
    {
        spin(
            fn () => $this->client->threads()->messages()->create($thread->id, [
                'role' => 'user',
                'content' => $message,
            ]),
            'Sending Message...'
        );

        $threadRun = spin(
            fn () => $this->client->threads()->runs()->create(
                threadId: $thread->id,
                parameters: [
                    'assistant_id' => config('droid.assistant_id'),
                ],
            ),
            'Executing the Thread...'
        );

        return $this->loadAnswer($threadRun);
    }

    /**
     * @throws \ReflectionException
     */
    public function loadAnswer(ThreadRunResponse $threadRun): string
    {
        $threadRun = spin(
            fn () => $this->retrieveThread($threadRun),
            'Fetching response...'
        );

        if ($threadRun->status === 'requires_action' && $threadRun->requiredAction->type === 'submit_tool_outputs') {
            $requiredAction = $threadRun->requiredAction->toArray();
            $toolCalls = $requiredAction['submit_tool_outputs']['tool_calls'];

            $toolOutputs = $this->handleTools($toolCalls);

            $response = $this->client->threads()->runs()->submitToolOutputs(
                threadId: $threadRun->threadId,
                runId: $threadRun->id,
                parameters: [
                    'tool_outputs' => $toolOutputs,
                ]
            );

            return $this->loadAnswer($response);

        }

        $messageList = $this->client->threads()->messages()->list(
            threadId: $threadRun->threadId,
        );

        $answer = $messageList->data[0]->content[0]->text->value;
        render(view('assistant', ['answer' => $answer]));

        return $answer;
    }

    public function retrieveThread($threadRun)
    {
        while (in_array($threadRun->status, ['queued', 'in_progress'])) {
            $threadRun = $this->client->threads()->runs()->retrieve(
                threadId: $threadRun->threadId,
                runId: $threadRun->id,
            );
        }

        return $threadRun;
    }
}
