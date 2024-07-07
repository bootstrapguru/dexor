<?php

namespace App\Utils;

use Dotenv\Dotenv;
use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage; // Import Artisan facade to run commands

use function Laravel\Prompts\password;

class OnBoardingSteps
{
    private string $configFile = '.config';

    /**
     * @throws Exception
     */
    public function isCompleted($droidCommand): bool
    {
        return $this->configurationFileExists()
            && $this->viewsFolderExists()
            && $this->APIKeysExist()
            && $this->setupDatabase($droidCommand);
    }

    private function viewsFolderExists(): bool
    {
        if (! Storage::disk('home')->exists('views')) {
            try {
                Storage::disk('home')->makeDirectory('views');
            } catch (Exception $ex) {
                return false;
            }
        }

        Config::set('view.compiled', Storage::disk('home')->path('views'));

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
    private function APIKeysExist(): bool
    {
        $services = ['openai', 'claude']; // List all supported services here

        foreach ($services as $service) {
            $apiKeyConfigName = strtoupper($service) . '_API_KEY';
            if (! config("services.{$service}.api_key")) {
                $apiKey = password(
                    label: "\uD83E\uDD16: Enter your {$service} API key to continue",
                    placeholder: 'sk-xxxxxx-xxxxxx-xxxxxx-xxxxxx',
                    hint: "You can find your API key in your {$service} dashboard"
                );
                $this->setConfigValue($apiKeyConfigName, $apiKey);
            }
        }

        return true;
    }

    protected function setupDatabase($droidCommand): bool
    {
        $databasePath = Storage::disk('home')->path('database.sqlite');

        if (!file_exists($databasePath)) {
            Storage::disk('home')->put('database.sqlite', '');
        }
        $droidCommand->call('migrate', ['--force' => true]);

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

        if (str_contains($value, "\n")) {
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
                $parsedKey = strtolower(str_replace('_API_KEY', '', $key));
                Config::set('services.'.strtolower($parsedKey).'.api_key', $value);
            }

            return true;
        }

        return false;
    }
}
