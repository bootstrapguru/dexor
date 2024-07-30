<?php

namespace App\Tools;

use App\Attributes\Description;
use Laravel\Dusk\Browser;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Laravel\Dusk\Chrome\ChromeProcess;
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
        #[Description('Timeout in seconds for waiting for the selector (default: 60)')]
        int $timeout = 60
    ): string {
        $chromeProcess = null;
        $driver = null;

        try {
            // Start ChromeDriver
            $chromeProcess = (new ChromeProcess)->toProcess();
            
            if (!$chromeProcess->isRunning()) {
                $chromeProcess->start();
            }

            $options = (new ChromeOptions)->addArguments([
                '--headless',
                '--disable-gpu',
                '--no-sandbox',
                '--window-size=1920,1080',
            ]);

            $capabilities = DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            );

            // Retry connection to ChromeDriver
            $driver = retry(5, function () use ($capabilities) {
                return RemoteWebDriver::create(
                    'http://localhost:9515', $capabilities, 120000, 120000
                );
            }, 50);

            $browser = new Browser($driver);

            $content = $browser->visit($url)
                ->waitFor($selector, $timeout)
                ->element($selector)->getText();

            render(view('tool', [
                'name' => 'ReadWebsiteWithDusk',
                'output' => "Successfully read content from {$url}",
            ]));

            return $content;
        } catch (Exception $e) {
            $output = "Error reading website: " . $e->getMessage() . "\n";
            $output .= "File: " . $e->getFile() . "\n";
            $output .= "Line: " . $e->getLine() . "\n";
            $output .= "Trace: " . $e->getTraceAsString();
            render(view('tool', [
                'name' => 'ReadWebsiteWithDusk',
                'output' => $output,
            ]));

            return $output;
        } finally {
            // Ensure that we always attempt to close the browser and stop the ChromeDriver
            if ($driver) {
                (new Browser($driver))->quit();
            }
            if ($chromeProcess && $chromeProcess->isRunning()) {
                $chromeProcess->stop();
            }
        }
    }
}