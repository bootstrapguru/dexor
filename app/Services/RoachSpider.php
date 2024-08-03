<?php
namespace App\Services;

use App\Utils\RoachLoggerExtension;
use RoachPHP\Http\Response;
use RoachPHP\Spider\BasicSpider;

class RoachSpider extends BasicSpider
{
    /**
     * @var string[]
     */
    public array $startUrls = [];

    public array $extensions = [
        RoachLoggerExtension::class,
    ];

    public function parse(Response $response): \Generator
    {
        $title = $response->filter('body')->text();

        yield $this->item([
            'title' => $title
        ]);
    }
}
