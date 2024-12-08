<?php

namespace App\Tools;

use App\Attributes\Description;
use Illuminate\Support\Facades\Storage;

use function Termwind\render;

#[Description('Update the content of an existing file at the specified path. Use this when you need to update the existing of a file after write_to_file returns a suggestion to merge the content.\nExpected format for `replace_objects`: [ { \\\"find\\\": \\\"text_to_find\\\", \\\"replace\\\": \\\"replacement_text\\\" }, ... ]')]
final class UpdateFile
{
    public function handle(
        #[Description('File path to write content to')]
        string $file_path,

        #[Description('JSON string format of objects containing text to find and text to replace. Each object should have `find`, `replace` keys and optionally `occurrence`.')]
        string $replace_objects_json,
    ): string {

        try {
            $replace_objects = json_decode($replace_objects_json, true);
        } catch (\Exception $e) {
            return 'Invalid JSON format for replace_objects: '.$replace_objects_json;
        }

        render(view('tool', [
            'name' => 'UpdateFile: '.$file_path,
            'output' => 'Replace objects: '.print_r($replace_objects, true),
        ]));

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
            'name' => 'UpdateFile: '.$file_path,
            'output' => 'Updating content in the file....',
        ]));

        try {
            // Loop through the objects and apply the changes
            foreach ($replace_objects as $object) {
                if (isset($object['find']) && isset($object['replace'])) {
                    $find = preg_quote($object['find'], '/');
                    $replace = $object['replace'];
                    $occurrence = isset($object['occurrence']) ? (int)$object['occurrence'] : -1;

                    if ($occurrence > 0) {
                        // Replace specific occurrence
                        $pattern = '/(' . $find . ')/';
                        $count = 0;
                        $fileContent = preg_replace_callback($pattern, function($matches) use (&$count, $occurrence, $replace) {
                            $count++;
                            return ($count == $occurrence) ? $replace : $matches[0];
                        }, $fileContent);
                    } else {
                        // Replace all occurrences
                        $fileContent = preg_replace('/' . $find . '/', $replace, $fileContent, -1, $count);
                    }

                    if ($count === 0) {
                        throw new \Exception("No replacements made for: " . $object['find']);
                    }
                }
            }
        }
        catch (\Exception $e) {
            render(view('tool', [
                'name' => 'UpdateFile: '.$file_path,
                'output' => 'Error updating the file: '.$e->getMessage(),
            ]));
            return 'Error updating the file: '.$e->getMessage();
        }

        // Update the file with the new content
        Storage::put($file_path, $fileContent);

        return 'The file has been updated successfully at '.$file_path.'!';
    }
}
