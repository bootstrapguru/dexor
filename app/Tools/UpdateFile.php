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

        #[Description('Array of objects containing text to find and text to replace. Each object should have `find` and `replace` keys.')]
        array $replace_objects,
    ): string {

        // Make sure it's a relative path
        if (str_contains($file_path, Storage::path(DIRECTORY_SEPARATOR))) {
            $file_path = str_replace(Storage::path(DIRECTORY_SEPARATOR), '', $file_path);
        }

        if (!Storage::exists($file_path)) {
            return 'The file does not exist: '.$file_path;
        }

        // Get the file content
        $fileContent = Storage::get($file_path);

        render(view('tool', [
            'name' => 'UpdateFile',
            'output' => 'Updating content in the file....',
        ]));

        var_dump($replace_objects);

        // Loop through the objects and apply the changes
        foreach ($replace_objects as $object) {
            if (isset($object['find']) && isset($object['replace'])) {
                // Replace the text in the file content
                $fileContent = str_replace($object['find'], $object['replace'], $fileContent);
            }
        }

        // Update the file with the new content
        Storage::put($file_path, $fileContent);

        return 'The file has been updated successfully at '.$file_path.'!';
    }
}
