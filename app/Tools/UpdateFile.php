<?php

namespace App\Tools;

use App\Attributes\Description;
use Illuminate\Support\Facades\Storage;

use function Termwind\render;

#[Description('Update the content of an existing file at the specified path. Use this when you need to update the existing of a file after write_to_file returns a suggestion to merge the content.')]
final class UpdateFile
{
    public function handle(
        #[Description('File path to write content to')]
        string $file_path,

        #[Description('Updated Content to overwrite the file')]
        string $content,
    ): string {

        // Make sure it's a relative path
        if (str_contains($file_path, Storage::path(DIRECTORY_SEPARATOR))) {
            $file_path = str_replace(Storage::path(DIRECTORY_SEPARATOR), '', $file_path);
        }

        $directory = dirname($file_path);

        // Ensure the directory exists
        if (! Storage::exists($directory)) {
            render(view('tool', [
                'name' => 'WriteToFile',
                'output' => 'Directory not found. Creating '.$directory,
            ]));
            Storage::makeDirectory($directory, 0755, true);
        }

        Storage::put($file_path, $content);

        $output = 'The file has been updated successfully at '.$file_path;
        render(view('tool', [
            'name' => 'WriteToFile',
            'output' => $output,
        ]));

        return $output;
    }
}
