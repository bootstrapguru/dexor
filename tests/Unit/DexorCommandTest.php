<?php

namespace Tests\Unit;

use App\Commands\DexorCommand;
use App\Models\Assistant;
use App\Services\ChatAssistant;
use App\Tools\ExecuteCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DexorCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_command_creates_and_updates_assistant()
    {
        $chatAssistant = $this->createMock(ChatAssistant::class);
        $executeCommand = $this->createMock(ExecuteCommand::class);
        $command = new DexorCommand($chatAssistant, $executeCommand);

        $assistant = Assistant::factory()->create(['current' => false]);

        // Mock the createNewAssistant method
        $newAssistant = $this->createMock(Assistant::class);
        $chatAssistant->method('createNewAssistant')->willReturn($newAssistant);
        $newAssistant->expects($this->once())->method('update')->with(['current' => true]);

        $this->artisan('dexor --new');

        $this->assertFalse($assistant->fresh()->current);
    }
}
