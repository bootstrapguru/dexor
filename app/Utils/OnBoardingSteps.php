<?php

namespace App\Utils;

use App\Services\ChatAssistant;
use Dotenv\Dotenv;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

use function Laravel\Prompts\password;
use function Laravel\Prompts\spin;
use function Termwind\render;

class OnBoardingSteps
{
    private string $configFile = '.droid_config';

    /**
     * @throws Exception
     */
    public function isCompleted(): bool
    {
        return $this->configurationFileExists()
            && $this->viewsFolderExists()
            && $this->APIKeyExists();
    }

    private function viewsFolderExists(): bool
    {
        if (! Storage::disk('home')->exists('.droid_views')) {
            try {
                Storage::disk('home')->makeDirectory('.droid_views');
            } catch (Exception $ex) {
                return false;
            }
        }

        Config::set('view.compiled', Storage::disk('home')->path('.droid_views'));

        return true;
    }

    /**
     * @throws Exception
     */
    private function configurationFileExists(): bool
    {
        if (! Storage::disk('home')->exists($this->configFile)) {
            try {
                Storage::disk('home')->put($this->configFile, '');
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
        if (! config('droid.api_key')) {
            $apiKey = password(
                label: '🤖: Enter your OpenAI API key to continue',
                placeholder: 'sk-xxxxxx-xxxxxx-xxxxxx-xxxxxx',
                hint: 'You can find your API key in your OpenAI dashboard'
            );
            $this->setConfigValue('DROID_API_KEY', $apiKey);
        }

        return true;
    }

    /**
     * @throws Exception
     */
    protected function setConfigValue($key, $value): bool
    {
        if (! $this->configurationFileExists()) {
            return false;
        }

        if (strpos($value, "\n") !== false) {
            $value = '"'.addslashes($value).'"';
        }

        $config = Storage::disk('home')->get($this->configFile);
        $pattern = "/^{$key}=.*/m";

        if (preg_match($pattern, $config)) {
            // Key exists, replace it with new value
            $config = preg_replace($pattern, "{$key}={$value}", $config);
        } else {
            $config .= "\n{$key}={$value}";
        }

        if (Storage::disk('home')->put($this->configFile, $config)) {
            $this->loadConfigFile();
            return true;
        }

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
                Config::set('droid.'.$parsedKey, $value);
            }

            return true;
        }

        return false;
    }
}
