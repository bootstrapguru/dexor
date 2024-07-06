<?php

namespace App\Commands;

use App\Models\Project;
use App\Services\ChatAssistant;
use App\Tools\ExecuteCommand;
use App\Utils\OnBoardingSteps;
use App\Services\Request\ChatRequest;
use App\Services\AIConnector;
use Exception;
use Illuminate\Console\Command;

use Illuminate\Support\Facades\DB;
use function Termwind\ask;
use function Termwind\render;

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
        if (! $onBoardingSteps->isCompleted()) {
            return self::FAILURE;
        }

        $chatAssistant = new ChatAssistant;
        $thread = $chatAssistant->createThread();

        render(view('assistant', [
            'answer' => 'How can I help you today?',
        ]));

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
