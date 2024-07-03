<?php

namespace App\Tools;

use Illuminate\Support\Facades\File;
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

        if (File::exists($basePath)) {
            // Get the file content
            $fileContent = File::get($basePath);

            // Append the new content to the file
            return 'The file already exists in the path, Make sure to merge your suggestion with the existing file without any breaking changes. Once, they are merged, call the update_file function. The current contents of the file are '. $fileContent;
        }

        $directory = dirname($basePath);

        // Ensure the directory exists
        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        File::put($basePath, $content);
        return 'The file has been created successfully at '.$file_path;
    }

}
