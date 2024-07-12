<?php

namespace App\Commands;

use App\Services\ChatAssistant;
use App\Tools\ExecuteCommand;
use App\Utils\OnBoardingSteps;
use Exception;
use Illuminate\Console\Command;

use function Termwind\ask;

class DroidCommand extends Command
{
    public $signature = 'droid {--new : Create a new assistant}';

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
            $this->info('New assistant created successfully.');
            return self::SUCCESS;
        }

        $thread = $this->chatAssistant->createThread();

        while (true) {
            $message = ask('<span class="mt-1 mx-1">🍻:</span>');

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