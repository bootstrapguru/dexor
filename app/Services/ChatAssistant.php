<?php

namespace App\Services;

use App\Data\AIModelData;
use App\Models\Assistant;
use App\Models\Project;
use App\Tools\ExecuteCommand;
use App\Tools\ListFiles;
use App\Tools\ReadFile;
use App\Tools\UpdateFile;
use App\Tools\CreateFile;
use App\Traits\HasTools;
use App\Utils\OnBoardingSteps;
use Exception;
use Illuminate\Support\Collection;
use ReflectionException;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

use function Laravel\Prompts\form;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Termwind\render;

class ChatAssistant
{
    use HasTools;

    private const DEFAULT_SERVICE = 'openai';
    private OnBoardingSteps $onBoardingSteps;

    /**
     * @throws ReflectionException
     */
    public function __construct(OnBoardingSteps $onBoardingSteps)
    {
        $this->onBoardingSteps = $onBoardingSteps;
        $this->register([
            ExecuteCommand::class,
            CreateFile::class,
            UpdateFile::class,
            ListFiles::class,
            ReadFile::class,
        ]);
    }

    /**
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function getCurrentProject(): Project
    {
        $projectPath = getcwd();
        $project = Project::where('path', $projectPath)->first();

        if ($project) {
            return $project;
        }

        $userChoice = select(
            label: 'No existing project found. Would you like to create a new assistant or use an existing one?',
            options: [
                'create_new' => 'Create New Assistant',
                'use_existing' => 'Use Existing Assistant',
            ]
        );

        $assistantId = match ($userChoice) {
            'create_new' => $this->createNewAssistant()->id,
            'use_existing' => $this->selectExistingAssistant(),
            default => throw new Exception('Invalid choice'),
        };

        return Project::create([
            'path' => $projectPath,
            'assistant_id' => $assistantId,
        ]);
    }

    /**
     * @throws FatalRequestException
     * @throws RequestException
     * @throws Exception
     */
    public function createNewAssistant(): Assistant
    {
        $path = getcwd();
        $folderName = basename($path);

        $service = $this->selectService();
        $this->ensureAPIKey($service);
        $models = $this->getModels($service);

        $assistant = form()
            ->text(label: 'What is the name of the assistant?', default: ucfirst($folderName.' Project'), required: true, name: 'name')
            ->text(label: 'What is the description of the assistant? (optional)', name: 'description')
            ->search(
                label: 'Choose the Model for the assistant',
                options: fn (string $value) => $this->filterModels($models, $value),
                name: 'model'
            )
            ->textarea(
                label: 'Customize the prompt for the assistant?',
                default: config('dexor.default_prompt', ''),
                required: true,
                hint: 'Include any project details that the assistant should know about.',
                rows: 20,
                name: 'prompt'
            )
            ->submit();

        return Assistant::create([
            'name' => $assistant['name'],
            'description' => $assistant['description'],
            'model' => $assistant['model'],
            'prompt' => $assistant['prompt'],
            'service' => $service,
        ]);
    }

    /**
     * @throws Exception
     */
    public function createThread()
    {
        $project = $this->getCurrentProject();
        $latestThread = $project->threads()->latest()->first();

        if ($latestThread && $this->shouldUseExistingThread()) {
            return $latestThread;
        }

        $thread = spin(
            fn () => $project->threads()->create([
                'assistant_id' => $project->assistant_id,
                'title' => 'New Thread',
            ]),
            'Creating New Thread...'
        );

        render(view('assistant', [
            'answer' => 'How can I help you?',
        ]));

        return $thread;
    }

    /**
     * @throws Exception
     */
    public function getAnswer($thread, ?string $message): string
    {
        if ($message !== null) {
            $thread->messages()->create([
                'role' => 'user',
                'content' => $message,
            ]);
        }

        $thread->load('messages');

        $service = $thread->assistant->service;
        $connector = $this->getConnector($service);
        $chatRequest = $this->getChatRequest($service, $thread);

        $message = spin(
            fn () => $connector->send($chatRequest)->dto(),
            "Getting response from {$thread->assistant->service}: {$thread->assistant->model}"
        );

        return $this->handleTools($thread, $message);
    }

    /**
     * @throws Exception
     */
    private function handleTools($thread, $message): string
    {
        $answer = $message->content;

        $thread->messages()->create($message->toArray());
        if ($message->tool_calls !== null && $message->tool_calls->isNotEmpty()) {
            $this->renderAnswer($answer);

            foreach ($message->tool_calls as $toolCall) {
                $this->executeToolCall($thread, $toolCall);
            }
            return $this->getAnswer($thread, null);
        }

        $this->renderAnswer($answer);
        return $answer;
    }

    private function selectService(): string
    {
        return select(
            label: 'Choose the Service for the assistant',
            options: array_keys(config('aiproviders')),
            default: self::DEFAULT_SERVICE
        );
    }

    /**
     * @throws Exception
     */
    private function getModels(string $service): Collection
    {
        $connectorClass = config("aiproviders.{$service}.connector");
        $listModelsRequestClass = config("aiproviders.{$service}.listModelsRequest");

        if ($listModelsRequestClass !== null) {
            $connector = new $connectorClass();
            return $connector->send(new $listModelsRequestClass())->dto();
        }

        return collect(config("aiproviders.{$service}.models"))
            ->map(fn ($model) => AIModelData::from(['name' => $model]));
    }

    private function filterModels(Collection $models, string $value): array
    {
        return strlen($value) > 0
            ? $models->filter(fn ($model) => str_contains($model->name, $value))->pluck('name')->toArray()
            : $models->take(5)->pluck('name')->toArray();
    }

    /**
     * @throws FatalRequestException
     * @throws RequestException
     */
    private function selectExistingAssistant(): int
    {
        $assistants = Assistant::all();
        if ($assistants->isEmpty()) {
            return $this->createNewAssistant()->id;
        }

        $options = $assistants->pluck('name', 'id')->toArray();
        return select(label: 'Select an assistant', options: $options);
    }

    private function shouldUseExistingThread(): bool
    {
        return select(
            label: 'Found Existing thread, do you want to continue the conversation or start new?',
            options: [
                'use_existing' => 'Continue',
                'create_new' => 'Start New Thread',
            ]
        ) === 'use_existing';
    }

    private function getConnector(string $service): object
    {
        $connectorClass = config("aiproviders.{$service}.connector");
        return new $connectorClass();
    }

    private function getChatRequest(string $service, $thread): object
    {
        $chatRequestClass = config("aiproviders.{$service}.chatRequest");
        return new $chatRequestClass($thread, $this->registered_tools);
    }

    private function renderAnswer(?string $answer): void
    {
        if ($answer) {
            render(view('assistant', ['answer' => $answer]));
        }
    }

    /**
     * @throws Exception
     */
    private function executeToolCall($thread, $toolCall): void
    {
        try {
            $toolResponse = $this->call(
                $toolCall->function->name,
                json_decode($toolCall->function->arguments, true, 512, JSON_THROW_ON_ERROR)
            );

            $thread->messages()->create([
                'role' => 'tool',
                'tool_call_id' => $toolCall->id,
                'name' => $toolCall->function->name,
                'content' => $toolResponse,
            ]);
        } catch (Exception $e) {
            throw new Exception("Error calling tool: {$e->getMessage()}");
        }
    }

    private function ensureAPIKey(string $service): void
    {
        $apiKeyConfigName = strtoupper($service).'_API_KEY';
        if (!config("aiproviders.{$service}.api_key")) {
            $this->onBoardingSteps->requestAPIKey($service);
        }
    }
}
