<?php

namespace App\Tools;

use App\Attributes\Description;
use App\Services\RoachSpider;
use Exception;

use RoachPHP\Roach;
use RoachPHP\Spider\Configuration\Overrides;
use function Termwind\render;

#[Description('Read content from a website using Laravel Dusk. Use this when you need to scrape content from a web page.')]
final class GetWebsiteContent
{
    public function handle(
        #[Description('URL of the website to read')]
        string $url,
        #[Description('CSS selector to target specific content (default: body)')]
        ?string $selector = 'body',
        #[Description('Timeout in seconds for waiting for the selector (default: 60)')]
        int $timeout = 60
    ): string {


        try {

            $content = Roach::collectSpider(
                RoachSpider::class,
                new Overrides(startUrls: [$url]),
            );

//            render(view('tool', [
//                'name' => 'GetWebsiteContent',
//                'output' => $content,
//            ]));

            return collect($content)->toJson();
        } catch (Exception $e) {

            render(view('tool', [
                'name' => 'Get Website Content',
                'output' => $e->getMessage(),
            ]));

            return $e->getMessage();
        }
    }
}
