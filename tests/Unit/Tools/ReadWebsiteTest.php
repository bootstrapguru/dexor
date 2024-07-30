<?php

namespace Tests\Unit\Tools;

use App\Tools\ReadWebsite;
use Tests\TestCase;
use Illuminate\Support\Facades\Http;

class ReadWebsiteTest extends TestCase
{
    public function test_read_website_successful()
    {
        Http::fake([
            'https://example.com' => Http::response('<html><body>Example content</body></html>', 200),
        ]);

        $readWebsite = new ReadWebsite();
        $result = $readWebsite('https://example.com');

        $this->assertEquals('<html><body>Example content</body></html>', $result);
    }

    public function test_read_website_error()
    {
        Http::fake([
            'https://example.com' => Http::response('Not Found', 404),
        ]);

        $readWebsite = new ReadWebsite();
        $result = $readWebsite('https://example.com');

        $this->assertStringStartsWith('Error: Unable to fetch content. Status code: 404', $result);
    }
}