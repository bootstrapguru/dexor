<?php

namespace App\Tools;

use App\Attributes\Description;
use Laravel\Dusk\Browser;
use Laravel\Dusk\Chrome\ChromeProcess;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Exception;

use function Termwind\render;

#[Description('Read content from a website using Laravel Dusk. Use this when you need to scrape content from a web page.')]
final class ReadWebsiteWithDusk
{
    public function handle(
        #[Description('URL of the website to read')]
        string $url,
        #[Description('CSS selector to target specific content (default: body)')]
        ?string $selector = 'body',
        #[Description('Timeout in seconds for waiting for the selector (default: 30)')]
        int $timeout = 30
    ): string {
        try {
            $options = (new ChromeOptions)->addArguments([
                '--headless',
                '--disable-gpu',
                '--no-sandbox',
                '--window-size=1920,1080',
            ]);

            $capabilities = DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            );

            $chromeProcess = (new ChromeProcess)->toProcess();
            if ($chromeProcess->isStarted()) {
                $chromeProcess->stop();
            }
            $chromeProcess->start();

            $seleniumServerUrl = config('dexor.selenium_server_url');
            $driver = RemoteWebDriver::create(
                $seleniumServerUrl, $capabilities
            );

            $browser = new Browser($driver);

            $content = $browser->visit($url)
                ->waitFor($selector, $timeout)
                ->element($selector)->getText();

            $browser->quit();
            $chromeProcess->stop();

            render(view('tool', [
                'name' => 'ReadWebsiteWithDusk',
                'output' => "Successfully read content from {$url}",
            ]));

            var_dump($content);

            return $content;
        } catch (Exception $e) {
            $output = "Error reading website: " . $e->getMessage();
            render(view('tool', [
                'name' => 'ReadWebsiteWithDusk',
                'output' => $output,
            ]));

            return $output;
        }
    }
}
