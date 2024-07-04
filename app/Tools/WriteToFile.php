<?php

namespace App\Tools;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use function Laravel\Prompts\info;
use App\Attributes\Description;

#[Description('Write content to an existing file at the specified path. Use this when you need to write content to a file.')]
final class WriteToFile {

    public function handle(
        #[Description('File path to write content to')]
        string $file_path,

        #[Description('Content to write to the file')]
        string $content,
    ): string {

        $basePath = base_path($file_path);

        if (Storage::exists($basePath)) {
            // Get the file content
            $fileContent = Storage::get($basePath);

            // Append the new content to the file
            return 'The file already exists in the path, Make sure to merge your suggestion with the existing file without any breaking changes. Once, they are merged, call the update_file function. The current contents of the file are '. $fileContent;
        }

        $directory = dirname($basePath);

        // Ensure the directory exists
        if (!Storage::exists($directory)) {
            Storage::makeDirectory($directory, 0755, true);
        }

        Storage::put($basePath, $content);
        info('The file has been created successfully at '.$file_path);
        return 'The file has been created successfully at '.$file_path;
    }

}
