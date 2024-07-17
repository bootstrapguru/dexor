<?php

namespace App\Services;

use App\Data\AIModelData;
use App\Models\Assistant;
use App\Models\Project;
use App\Tools\ExecuteCommand;
use App\Tools\ListFiles;
use App\Tools\ReadFile;
use App\Tools\UpdateFile;
use App\Tools\WriteToFile;
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
            WriteToFile::class,
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
        $project = Project::where('path', $path)->first();

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

        $assistantData = [
            'name' => $assistant['name'],
            'description' => $assistant['description'],
            'model' => $assistant['model'],
            'prompt' => $assistant['prompt'],
            'service' => $service,
        ];

        if ($project && $project->assistant) {
            // Update existing assistant
            $project->assistant->update($assistantData);
            return $project->assistant->fresh();
        } else {
            // Create new assistant
            return Assistant::create($assistantData);
        }
    }

    // ... (rest of the file remains unchanged)
}