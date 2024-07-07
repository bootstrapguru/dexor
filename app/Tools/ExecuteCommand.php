<?php

namespace App\Tools;

use App\Attributes\Description;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

use function Termwind\render;

#[Description('Executes a terminal command and returns the output. Use this when you need to execute a terminal command like git and other framework commands')]
final class ExecuteCommand
{
    public function handle(
        #[Description('The command to execute.')]
        string $command,
    ): string {
        $process = Process::fromShellCommandline($command);

        try {
            $process->mustRun();

            $output = $process->getOutput();
            render(view('tool', [
                'name' => 'ExecuteCommand: '.$command,
                'output' => $output,
            ]));

            return $output;
        } catch (ProcessFailedException $exception) {
            $output = 'The command failed: '.$exception->getMessage();
            render(view('tool', [
                'name' => 'ExecuteCommand Failed: '.$command,
                'output' => $output,
            ]));

            return $output;
        }
    }
}
