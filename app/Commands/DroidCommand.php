<?php

namespace App\Commands;

use App\Services\ChatAssistant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use function Laravel\Prompts\spin;
use function Termwind\{ask, render};

class DroidCommand extends Command
{
    public $signature = 'droid';

    public $description = 'Allows you to create/update a feature';

    public function handle(): int
    {
        $chatAssistant = new ChatAssistant;
        if (!config('droid.assistant_id') &&
            $answer = ask(<<<HTML
                <span class="mt-1 mr-1 px-1">
                    🤖: Looks like you have not set up your assistant yet. Do you want me create an assistant now?
                </span>
            HTML)
        ) {
            if ($answer === 'no') {
                render('<div class="px-1 pt-1">🤖: Okay, you can always run `droid` to set up your assistant later.</div>');
                return self::SUCCESS;
            }

            $response = spin(
                fn () => $chatAssistant->createAssistant(),
                'Creating an assistant...'
            );

            $this->setEnvValue('DROID_ASSISTANT_ID', $response->id);
            render('<div class="px-1 pt-1">🤖: <span class="font-bold bg-green-300 text-black">'.$response->name.'</span> has been created successfully 🎉 Please run droid again to start using your assistant.</div>');
            return self::SUCCESS;
        }

        $threadRun = $chatAssistant->createThread();
        render(<<<HTML
                <span class="mt-1 mr-1 px-1">
                    🤖: How can I help you today?
                </span>
            HTML);

        while (true) {
            $message = ask('<span class="mt-1 mx-1">🍻:</span>');

            if ($message === 'exit') {
                break;
            }

            $response = $chatAssistant->getAnswer($threadRun, $message);
            $this->info('Assistant: ' . $response);
        }

        return self::SUCCESS;
    }

    protected function setEnvValue($key, $value): void
    {
        $envFilePath = base_path('.droid_config');

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
