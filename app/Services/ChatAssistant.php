<?php

namespace App\Services;

use App\Models\Assistant;
use App\Models\Project;
use App\Services\Request\ChatRequest;
use App\Tools\ExecuteCommand;
use App\Tools\ListFiles;
use App\Tools\ReadFile;
use App\Tools\UpdateFile;
use App\Tools\WriteToFile;
use App\Traits\HasTools;
use Exception;
use ReflectionException;

use function Laravel\Prompts\form;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Termwind\render;

class ChatAssistant
{
    use HasTools;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        // register the tools
        $this->register([
            ExecuteCommand::class,
            WriteToFile::class,
            UpdateFile::class,
            ListFiles::class,
            ReadFile::class,
        ]);
    }

    /**
     * @throws Exception
     */
    public function getCurrentProject(): Project
    {
        $projectPath = getcwd();
        $project = Project::where('path', $projectPath)->first();

        if (! $project) {
            $userChoice = select(
                'No existing project found. Would you like to create a new assistant or use an existing one?',
                [
                    'create_new' => 'Create New Assistant',
                    'use_existing' => 'Use Existing Assistant',
                ]
            );

            switch ($userChoice) {
                case 'create_new':
                    $assistantId = $this->createNewAssistant()->id;
                    break;
                case 'use_existing':
                    $assistants = Assistant::all();
                    if ($assistants->isEmpty()) {
                        $assistantId = $this->createNewAssistant()->id;
                    } else {
                        $options = $assistants->pluck('name', 'id')->toArray();
                        $assistantId = select('Select an assistant', $options);
                    }
                    break;
                default:
                    throw new Exception('Invalid choice');
            }

            $project = Project::create([
                'path' => $projectPath,
                'assistant_id' => $assistantId,
            ]);
        }

        return $project;
    }

    public function createNewAssistant()
    {
        $path = getcwd();
        // get Folder name from path
        $folderName = basename($path);

        $assistant = form()
            ->text(label: 'What is the name of the assistant?', default: ucfirst($folderName.' Project'), required: true, name: 'name')
            ->text(label: 'What is the description of the assistant? (optional)', name: 'description')
            ->select(
                label: '🤖 Choose the Model for the assistant',
                options: ['gpt-4o', 'gpt-4-turbo', 'gpt-4-turbo-preview', 'gpt-3.5-turbo'],
                default: 'gpt-3.5-turbo',
                hint: 'The model to use for the assistant.',
                name: 'model'
            )
            ->textarea(
                label: 'Customize the prompt for the assistant?',
                default: config('droid.prompt') ?? '',
                required: true,
                hint: 'Make sure to include any details of the project that the assistant should know about. For example, type of framework, language, etc.',
                rows: 20,
                name: 'prompt'
            )
            ->submit();

        return Assistant::create([
            'name' => $assistant['name'],
            'description' => $assistant['description'],
            'model' => $assistant['model'],
            'prompt' => $assistant['prompt'],
        ]);
    }

    /**
     * @throws Exception
     */
    public function createThread()
    {
        $project = $this->getCurrentProject();
        $threadTitle = 'New Thread';

        return spin(
            fn () => $project->threads()->create([
                'assistant_id' => $project->assistant_id,
                'title' => $threadTitle,
            ]),
            'Creating New Thread...'
        );
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    public function getAnswer($thread, $message): string
    {
        if ($message !== null) {
            $thread->messages()->create([
                'role' => 'user',
                'content' => $message,
            ]);
        }

        $thread->load('messages');

        $connector = new AIConnector;
        $chatRequest = new ChatRequest($thread, $this->registered_tools);
        $response = $connector->send($chatRequest)->json();

        $choice = $response['choices'][0];

        return $this->handleTools($thread, $choice);
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    public function handleTools($thread, $choice): string
    {
        $answer = $choice['message']['content'];

        $thread->messages()->create($choice['message']);

        if ($choice['finish_reason'] === 'tool_calls') {

            foreach ($choice['message']['tool_calls'] as $toolCall) {
                try {
                    $toolResponse = $this->call($toolCall['function']['name'], json_decode($toolCall['function']['arguments'], true));

                    $thread->messages()->create([
                        'role' => 'tool',
                        'tool_call_id' => $toolCall['id'],
                        'name' => $toolCall['function']['name'],
                        'content' => $toolResponse,
                    ]);

                } catch (Exception $e) {
                    throw new Exception('Error calling tool: '.$e->getMessage());
                }
            }

            // return the tool response to the AI to continue the conversation
            return $this->getAnswer($thread, null);
        }

        render(view('assistant', ['answer' => $answer]));

        return $answer;
    }
}
