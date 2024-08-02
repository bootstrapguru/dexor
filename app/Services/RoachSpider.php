<?php
namespace App\Services;

use RoachPHP\Http\Response;
use RoachPHP\Spider\BasicSpider;

class RoachSpider extends BasicSpider
{
    /**
     * @var string[]
     */
    public array $startUrls = [
        'https://docs.saloon.dev/'
    ];

    public function parse(Response $response): \Generator
    {
        $title = $response->filter('body')->text();

        yield $this->item([
            'title' => $title
        ]);
    }
}
