<?php

namespace App\Services;

use App\Tools\UpdateFile;
use App\Tools\WriteToFile;
use App\Tools\ListFiles;
use App\Tools\ReadFile;
use Exception;
use Illuminate\Support\Facades\Log;
use OpenAI;
use OpenAI\Client;
use OpenAI\Responses\Threads\Runs\ThreadRunResponse;
use App\Traits\HasTools;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\info;

class ChatAssistant
{

    use HasTools;

    private Client $client;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->client = OpenAI::client(config('droid.ai_key'));
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
            'model' => config('droid.ai_model'),
            'description' => 'Droid Dev is a code generation assistant for Web applications',
            'instructions' => config('droid.prompt') ?? 'You are an AI assistant called Droid, skilled in software development and code generation. The current codebase is a Laravel application with Jetsream, Inertia.js with Vue, and Tailwind CSS.
            You will receive instructions on a feature request or bug fix.
            Your task is to generate the necessary code changes for a web application to implement the feature and write the changes to the files.
            Follow the workflow outlined below and use the provided tools to achieve the desired outcome.

            Workflow
            Understand the Feature Request

            Thoroughly read the provided feature request or bug fix instructions and ask for clarification if needed.
            List Existing Files and Directories to Understand the Codebase and Structure.

            Use the list_files function to list all files and subdirectories in the specified path to understand the current structure.
            Create or Update Necessary Files

            Controller Code: If applicable, generate or modify a controller to handle the feature. If the file already exists, use the read_file function to get the current content, apply the changes, and then use the update_file function.
            Route Definitions: Define the necessary routes and ensure they are appended to the existing routes file without replacing existing content. Use read_file and update_file functions as needed.
            Views: Generate or modify view files. Before creating new view files, use the list_files function to check the resources directory and understand any existing frontend technologies or design patterns. Use read_file to follow similar code styles and design. Reuse layouts and components where possible.
            Model Code: If applicable, generate or modify models. Use read_file and update_file functions if the file exists.
            Migrations: Create or modify database migrations.
            Tests: Write feature tests to ensure the new functionality works as expected. Do not make changes to .env files.
            Ensure that any new code is properly formatted and follows best practices. If existing files need to be modified, append the new code appropriately without overwriting the existing content.',
            'tools' => array_values($this->registered_tools),
        ]);

    }

    public function createThread()
    {
        return spin(
            fn () => $this->client->threads()->create([]),
            'Creating New Thread...'
        );
    }

    public function getAnswer($thread, $message)
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

    public function loadAnswer(ThreadRunResponse $threadRun)
    {
        $threadRun = spin(
            fn () => $this->retrieveThread($threadRun),
            'Fetching response...'
        );

        if ($threadRun->status  === 'requires_action' && $threadRun->requiredAction->type === 'submit_tool_outputs') {
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

        return $messageList->data[0]->content[0]->text->value;
    }

    public function retrieveThread($threadRun)
    {
        while(in_array($threadRun->status, ['queued', 'in_progress'])) {
            $threadRun = $this->client->threads()->runs()->retrieve(
                threadId: $threadRun->threadId,
                runId: $threadRun->id,
            );
        }

        return $threadRun;
    }

}
