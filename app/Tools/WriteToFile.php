<?php

namespace App\Tools;

use App\Attributes\Description;
use Illuminate\Support\Facades\Storage;
use function Termwind\render;

#[Description('Write content to an existing file at the specified path. Use this when you need to write content to a file.')]
final class WriteToFile {

    public function handle(
        #[Description('Relative File path to write content to')]
        string $file_path,

        #[Description('Content to write to the file')]
        string $content,
    ): string {

        render('WriteToFile before: '. $file_path);
        // Make sure it's a relative path
        if (str_contains($file_path, Storage::path(DIRECTORY_SEPARATOR))) {
            render('WriteToFile got absolute path');
            $file_path = str_replace(Storage::path(DIRECTORY_SEPARATOR), '', $file_path);
        }
        render('WriteToFile before: '. $file_path);

        if (Storage::exists($file_path)) {
            // Get the file content
            $fileContent = Storage::get($file_path);

            // Append the new content to the file
            return 'The file already exists in the path, Make sure to merge your suggestion with the existing file without any breaking changes. Once, they are merged, call the update_file function. The current contents of the file are '. $fileContent;
        }

        $directory = dirname($file_path);

        // Ensure the directory exists
        if (!Storage::exists($directory)) {
            Storage::makeDirectory($directory, 0755, true);
        }

        Storage::put($file_path, $content);

        return 'Created File: '.$file_path;
    }

}
