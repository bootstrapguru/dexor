<?php

namespace App\Tools;

use App\Attributes\Description;
use Illuminate\Support\Facades\Storage;

use function Termwind\render;

#[Description('Create content in a new file at the specified path. This tool allows you to create files with initial content.')]
final class CreateFile
{
    public function handle(
        #[Description('Relative File path to create the file at')]
        string $file_path,

        #[Description('Initial content to write to the file')]
        string $content,
    ): string {

        // Make sure it's a relative path
        if (str_contains($file_path, Storage::path(DIRECTORY_SEPARATOR))) {
            $file_path = str_replace(Storage::path(DIRECTORY_SEPARATOR), '', $file_path);
        }

        if (Storage::exists($file_path)) {
            render(view('tool', [
                'name' => 'CreateFile',
                'output' => 'The file already exists; please choose a different name or update it instead.',
            ]));
            return 'The file already exists: '.$file_path;
        }

        $directory = dirname($file_path);

        // Ensure the directory exists
        if (! Storage::exists($directory)) {
            Storage::makeDirectory($directory, 0755, true);
        }

        Storage::put($file_path, $content);

        $output = 'Created File: '.$file_path;
        render(view('tool', [
            'name' => 'CreateFile',
            'output' => $output,
        ]));

        return $output;
    }
}