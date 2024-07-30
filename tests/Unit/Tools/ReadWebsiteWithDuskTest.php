<?php

namespace Tests\Unit\Tools;

use App\Tools\ReadWebsiteWithDusk;
use Tests\TestCase;
use Laravel\Dusk\Browser;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Mockery;

class ReadWebsiteWithDuskTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock the Browser class
        $this->mock = Mockery::mock('overload:' . Browser::class);
    }

    public function test_read_website_successful()
    {
        $expectedContent = '<html><body>Example content</body></html>';
        
        $this->mock->shouldReceive('visit')->with('https://example.com')->andReturnSelf();
        $this->mock->shouldReceive('waitFor')->with('body', 30)->andReturnSelf();
        $this->mock->shouldReceive('element')->with('body')->andReturnSelf();
        $this->mock->shouldReceive('getText')->andReturn($expectedContent);
        $this->mock->shouldReceive('quit');

        $readWebsite = new ReadWebsiteWithDusk();
        $result = $readWebsite('https://example.com');

        $this->assertEquals($expectedContent, $result);
    }

    public function test_read_website_error()
    {
        $this->mock->shouldReceive('visit')->with('https://example.com')->andThrow(new \Exception('Connection failed'));

        $readWebsite = new ReadWebsiteWithDusk();
        $result = $readWebsite('https://example.com');

        $this->assertStringStartsWith('Error: Connection failed', $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}