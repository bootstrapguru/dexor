<?php

namespace App\Commands;

use App\Services\ChatAssistant;
use App\Utils\OnBoardingSteps;
use Exception;
use Illuminate\Console\Command;

use function Termwind\ask;

class DroidCommand extends Command
{
    public $signature = 'droid';

    public $description = 'Allows you to create/update a feature';

    /**
     * @throws Exception
     */
    public function handle(): int
    {
        $onBoardingSteps = new OnBoardingSteps();
        if (! $onBoardingSteps->isCompleted($this)) {
            return self::FAILURE;
        }

        $chatAssistant = new ChatAssistant;
        $thread = $chatAssistant->createThread();

        while (true) {
            $message = ask('<span class="mt-1 mx-1">🍻:</span>');

            if ($message === 'exit') {
                break;
            }

            $chatAssistant->getAnswer($thread, $message);
        }

        return self::SUCCESS;
    }
}
