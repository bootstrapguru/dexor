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
use Illuminate\Support\Facades\Log;
use function Laravel\Prompts\form;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Termwind\render;

class ChatAssistant
{
    use HasTools;

    /**
     * ChatAssistant constructor.
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
     * Get the current project based on the working directory.
     *
     * @return Project
     * @throws Exception
     */
    public function getCurrentProject(): Project
    {
        $projectPath = getcwd();
        $project = Project::where('path', $projectPath)->first();

        if (!$project) {
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
                        $assistantId = select('Select an assistant.', $options);
                    }
                    break;
                default:
                    throw new Exception('Invalid choice.');
            }

            $project = Project::create([
                'path' => $projectPath,
                'assistant_id' => $assistantId,
            ]);
        }

        return $project;
    }

    /**
     * Create a new assistant using a form prompt.
     *
     * @return Assistant
     */
    public function createNewAssistant(): Assistant
    {
        $path = getcwd();
        $folderName = basename($path);

        $assistant = form()
            ->text(label: 'What is the name of the assistant?', default: ucfirst($folderName . ' Project'), required: true, name: 'name')
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
                default: config('droid.prompt'),
                required: true,
                hint: 'Include project details like framework, language, etc.',
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
     * Create a new thread for the current project.
     *
     * @return Thread
     * @throws Exception
     */
    public function createThread()
    {
        $project = $this->getCurrentProject();
        $threadTitle = 'New Thread';

        return spin(
            fn() => $project->threads()->create([
                'assistant_id' => $project->assistant_id,
                'title' => $threadTitle,
            ]),
            'Creating New Thread...'
        );
    }

    /**
     * Get an answer from the AI assistant.
     *
     * @param $thread
     * @param $message
     * @return string
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

        return spin(
            function () use ($thread) {
                return $this->getAIResponse($thread);
            },
            'Fetching response from AI...'
        );
    }

    /**
     * Get the AI response and handle tools if necessary.
     *
     * @param $thread
     * @return string
     * @throws ReflectionException
     * @throws Exception
     */
    private function getAIResponse($thread): string
    {
        $connector = new AIConnector;
        $chatRequest = new ChatRequest($thread, $this->registered_tools);
        $response = $connector->send($chatRequest)->json();
        $choice = $response['choices'][0];

        // First handle tools if present
        while ($choice['finish_reason'] === 'tool_calls') {

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
                    Log::error('Error calling tool: ' . $e->getMessage());
                    throw new Exception('Error calling tool: ' . $e->getMessage());
                }
            }

            // Fetch the next part of the response including the tool calls
            $chatRequest = new ChatRequest($thread, $this->registered_tools);
            $response = $connector->send($chatRequest)->json();
            $choice = $response['choices'][0];
        }

        // Handle the final answer
        $answer = $choice['message']['content'];
        $thread->messages()->create($choice['message']);
        render(view('assistant', ['answer' => $answer]));
        
        return $answer;
    }
}
