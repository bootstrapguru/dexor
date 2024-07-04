<?php

namespace App\Commands;

use App\Services\ChatAssistant;
use App\Utils\OnBoardingSteps;
use Exception;
use Illuminate\Console\Command;
use function Termwind\{ask, render};


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
        if (!$onBoardingSteps->isCompleted()) {
            return self::FAILURE;
        }

        $chatAssistant = new ChatAssistant;

        $threadRun = $chatAssistant->createThread();
        render(view('assistant', [
            'answer' => 'How can I help you today?',
        ]));

        while (true) {
            $message = ask('<span class="mt-1 mx-1">🍻:</span>');

            if ($message === 'exit') {
                break;
            }

           $chatAssistant->getAnswer($threadRun, $message);
        }

        return self::SUCCESS;
    }
}
