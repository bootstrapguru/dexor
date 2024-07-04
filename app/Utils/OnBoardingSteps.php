<?php

namespace App\Utils;

use App\Services\ChatAssistant;
use Dotenv\Dotenv;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\spin;
use function Termwind\ask;
use function Termwind\render;

class OnBoardingSteps
{

    private string $configFile = '.droid_config';

    /**
     * @throws Exception
     */
    public function isCompleted(): bool
    {
        if (!$this->configurationFileExists()) {
            return false;
        }

        if (!$this->APIKeyExists()) {
            return false;
        }

        if (!$this->assistantExists()) {
            return false;
        }

        return true;
    }

    /**
     * @throws Exception
     */
    private function configurationFileExists(): bool
    {
        if (!Storage::disk('home')->exists($this->configFile)) {
            try {
                // create the config file from the internal config file
                Storage::disk('home')->put($this->configFile, Storage::disk('internal')->get(str_replace('.', '', $this->configFile)));
            } catch (Exception $ex) {
                return false;
            }
        }

        return true;
    }

    /**
     * @throws Exception
     */
    private function APIKeyExists(): bool
    {
        if (!config('droid.api_key')) {
            $apiKey = ask(<<<HTML
                <span class="mt-1 mr-1 px-1">
                    🤖: I don't see an API key added. Enter your OpenAI API key to continue
                </span>
            HTML);
            $this->setConfigValue('DROID_API_KEY', $apiKey);
            Config::set('droid.api_key', $apiKey);
        }

        return true;
    }

    /**
     * @throws Exception
     */
    public function assistantExists(): bool
    {
        $chatAssistant = new ChatAssistant;

        if (!config('droid.assistant_id')) {

            $answer = confirm('🤖: Looks like you have not set up your assistant yet. Do you want me create it now?');

            if (!$answer) {
                render('<div class="px-1 pt-1">🤖: Okay, you can always run `droid` to set up your assistant later.</div>');
                return true;
            }

            $response = spin(
                fn () => $chatAssistant->createAssistant(),
                'Creating an assistant...'
            );

            if (!$response) {
                error('Failed to create the assistant');
                return false;
            }
            $this->setConfigValue('DROID_ASSISTANT_ID', $response->id);
            render('<div class="px-1 pt-1">🤖: <span class="font-bold bg-green-300 text-black">'.$response->name.'</span> has been created successfully 🎉.</div>');
            return true;
        }

        return true;
    }

    /**
     * @throws Exception
     */
    protected function setConfigValue($key, $value): bool
    {
        if (!$this->configurationFileExists()) {
            error('Failed to set the configuration value');
            return false;
        }

        $config = Storage::disk('home')->get($this->configFile);
        $pattern = "/^{$key}=.*/m";

        if (preg_match($pattern, $config)) {
            // Key exists, replace it with new value
            $config = preg_replace($pattern, "{$key}={$value}", $config);
        } else {
            // Key does not exist, append it
            $config .= "\n{$key}={$value}";
        }

        if (Storage::disk('home')->put($this->configFile, $config)) {
            // Reload the environment file
            $this->loadConfigFile();
            return true;
        }

        error('Failed to set the configuration value');
        return false;
    }

    /**
     * @throws Exception
     */
    public function loadConfigFile(): bool
    {
        $this->configurationFileExists();

        $path = Storage::disk('home')->path($this->configFile);
        if (Storage::disk('home')->exists($this->configFile)) {
            $dotenv = Dotenv::createImmutable(dirname($path), basename($path));
            $envValues = $dotenv->load();

            foreach ($envValues as $key => $value) {
                $parsedKey = strtolower(str_replace('DROID_', '', $key));
                Config::set('droid.' . $parsedKey, $value);
            }

            return true;
        }

        return false;
    }
}
