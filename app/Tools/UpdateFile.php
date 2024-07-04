<?php

namespace App\Tools;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use function Laravel\Prompts\info;
use App\Attributes\Description;

#[Description('Update the content of an existing file at the specified path. Use this when you need to update the existing of a file after write_to_file returns a suggestion to merge the content.')]
final class UpdateFile {

    public function handle(
        #[Description('File path to write content to')]
        string $file_path,

        #[Description('Updated Content to overwrite the file')]
        string $content,
    ): string {

        $basePath = base_path($file_path);
        $directory = dirname($basePath);

        // Ensure the directory exists
        if (!Storage::exists($directory)) {
            Storage::makeDirectory($directory, 0755, true);
        }

        Storage::put($basePath, $content);
        info('The file has been updated successfully at '.$file_path);
        return 'The file has been updated successfully at '.$file_path;
    }

}
