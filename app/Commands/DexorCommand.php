<?php

namespace App\Commands;

use App\Prompts\UserInput;
use App\Services\ChatAssistant;
use App\Tools\ExecuteCommand;
use App\Utils\OnBoardingSteps;
use Exception;
use Illuminate\Console\Command;

use Laravel\Prompts\TextareaPrompt;
use function Laravel\Prompts\textarea;
use function Termwind\ask;

class DexorCommand extends Command
{
    public $signature = 'dexor {--new : Create a new assistant}';

    public $description = 'Allows you to create/update a feature, run commands, and create a new assistant';

    public function __construct(
        private readonly ChatAssistant  $chatAssistant,
        private readonly ExecuteCommand $executeCommand
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function handle(): int
    {
        $onBoardingSteps = new OnBoardingSteps();
        if (! $onBoardingSteps->isCompleted($this)) {
            return self::FAILURE;
        }

        if ($this->option('new')) {
            $this->chatAssistant->createNewAssistant();
        }

        $thread = $this->chatAssistant->createThread();

        while (true) {
            $message = (new UserInput('User: '))->prompt();
//            $message = textarea('🍻:');

            if ($message === 'exit') {
                break;
            }

            if (str_starts_with($message, '/')) {
                $this->executeCommand->handle(substr($message, 1));
            } else {
                $this->chatAssistant->getAnswer($thread, $message);
            }
        }

        return self::SUCCESS;
    }
}
