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
use App\Services\PrismAdapter;
use App\Services\ToolAdapter;
use App\Utils\OnBoardingSteps;
use Exception;
use Illuminate\Support\Collection;
use ReflectionException;

use function Laravel\Prompts\form;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;
use function Termwind\render;

class ChatAssistant
{
    private const DEFAULT_SERVICE = 'openai';
    private OnBoardingSteps $onBoardingSteps;
    private PrismAdapter $prismAdapter;
    private ToolAdapter $toolAdapter;
    private array $registered_tools = [];

    /**
     * @throws ReflectionException
     */
    public function __construct(OnBoardingSteps $onBoardingSteps)
    {
        $this->onBoardingSteps = $onBoardingSteps;
        $this->prismAdapter = new PrismAdapter();
        $this->toolAdapter = new ToolAdapter();
        
        // For now, we'll use simplified tools directly in PrismAdapter
        $this->registered_tools = [];
    }

    /**
     * @throws Exception
     */
    public function getCurrentProject(bool $isNew): Project
    {
        $projectPath = getcwd();
        $project = Project::where('path', $projectPath)->first();

        if ($isNew && $project) {
            // Update the existing project if isNew is true
            $project->assistant_id = $this->createNewAssistant()->id; // Update based on new assistant
            $project->save();
            return $project;
        }

        if (!$project) {
            // If there's no existing project, create a new one
            $userChoice = select(
                label: 'No project found. Would you like to create a new assistant or use an existing one?',
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

        return $project;
    }

    /**
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
            'service' => $service
        ]);
    }

    /**
     * @throws Exception
     */
    public function createThread(bool $isNew): \App\Models\Thread
    {
        $project = $this->getCurrentProject($isNew);
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

        // Check if service is supported by Prism
        if (!in_array($service, ['openai', 'claude', 'ollama', 'openrouter', 'deep_seek'])) {
            throw new Exception("Service {$service} is not supported by Prism");
        }

        $message = spin(
            fn () => $this->prismAdapter->sendChatRequest($thread, $this->getRegisteredToolDefinitions()),
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

        $messageData = [
            'role' => $message->role,
            'content' => $message->content,
        ];

        // With PrismPHP, tool calls are executed internally and the final response includes the result
        // We just need to save the assistant's response
        $thread->messages()->create($messageData);

        $this->renderAnswer($answer);
        return $answer;
    }

    private function selectService(): string
    {
        $availableServices = [
            'openai' => 'OpenAI',
            'claude' => 'Claude (Anthropic)',
            'ollama' => 'Ollama (Local)',
            'openrouter' => 'OpenRouter',
            'deep_seek' => 'DeepSeek',
        ];
        
        return select(
            label: 'Choose the Service for the assistant',
            options: $availableServices,
            default: self::DEFAULT_SERVICE
        );
    }

    /**
     * @throws Exception
     */
    private function getModels(string $service): Collection
    {
        $models = $this->prismAdapter->getModels($service);
        return collect($models)->map(fn ($model) => AIModelData::from(['name' => $model]));
    }

    private function filterModels(Collection $models, string $value): array
    {
        return strlen($value) > 0
            ? $models->filter(fn ($model) => str_contains($model->name, $value))->pluck('name')->toArray()
            : $models->take(5)->pluck('name')->toArray();
    }

    /**
     * @throws Exception
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

    /**
     * Get registered tool definitions for Prism
     */
    private function getRegisteredToolDefinitions(): array
    {
        // Return empty array since we're using PrismTools directly
        return [];
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
        // Tool execution is now handled directly by PrismPHP
        // This method is kept for compatibility but won't be called
        // as Prism handles tool execution internally
        throw new Exception("Tool execution should be handled by PrismPHP internally");
    }

    private function ensureAPIKey(string $service): void
    {
        // Map service names to Prism config keys
        $prismConfigKey = match ($service) {
            'openai' => 'prism.providers.openai.api_key',
            'claude' => 'prism.providers.anthropic.api_key',
            'ollama' => null, // Ollama doesn't need API key
            'openrouter' => 'prism.providers.openrouter.api_key',
            'deep_seek' => 'prism.providers.deepseek.api_key',
            default => null,
        };
        
        if ($prismConfigKey && !config($prismConfigKey)) {
            $this->onBoardingSteps->requestAPIKey($service);
        }
    }
}
