<?php

namespace Tests\Unit;

use App\Models\Assistant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssistantTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_update_assistant_name()
    {
        // Arrange
        $assistant = Assistant::create([
            'name' => 'Original Assistant',
            'model' => 'gpt-3.5-turbo',
            'prompt' => 'This is a test prompt.',
        ]);

        // Act
        $assistant->update(['name' => 'Updated Assistant']);

        // Assert
        $this->assertEquals('Updated Assistant', $assistant->fresh()->name);
    }
}