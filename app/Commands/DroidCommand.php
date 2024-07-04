<?php

namespace App\Commands;

use App\Services\ChatAssistant;
use App\Services\FileTreeLister;
use App\Utils\OnBoardingSteps;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use function Laravel\Prompts\{spin, text};
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

    protected function setEnvValue($key, $value): void
    {
        $envFilePath = Storage::path('.droid_config');

        if (!Storage::exists($envFilePath)) {
            $this->error('.env file does not exist. Create .env file inside your project root directory and try again.');
            return;
        }

        $envContent = Storage::get($envFilePath);
        $pattern = "/^{$key}=.*/m";

        if (preg_match($pattern, $envContent)) {
            // Key exists, replace it with new value
            $envContent = preg_replace($pattern, "{$key}={$value}", $envContent);
        } else {
            // Key does not exist, append it
            $envContent .= "\n{$key}={$value}";
        }

        Storage::put($envFilePath, $envContent);
    }
}
