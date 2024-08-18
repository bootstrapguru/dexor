<?php

namespace App\Tools;

use App\Attributes\Description;
use Illuminate\Support\Facades\Storage;

use function Termwind\render;

#[Description('Update the content of an existing file at the specified path. Use this when you need to update the existing content of a file after write_to_file returns a suggestion to merge the content. Expected format for `replace_objects`: [ { "find": "text_to_find", "replace": "replacement_text", "occurrence": n }, ... ]')]
final class UpdateFile
{
    public function handle(
        #[Description('File path to write content to')]
        string $file_path,

        #[Description('JSON string format of objects containing text to find and text to replace. Each object should have `find`, `replace` keys and optionally `occurrence`.')]
        string $replace_objects_json,
    ): string {

        // Decode the JSON string
        $replace_objects = json_decode($replace_objects_json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return 'Invalid JSON format for replace_objects: ' . json_last_error_msg();
        }

        render(view('tool', [
            'name' => 'UpdateFile: ' . $file_path,
            'output' => 'Replace objects: ' . print_r($replace_objects, true),
        ]));

        // Ensure the file path is relative to the storage root
        $file_path = str_replace(Storage::path(''), '', $file_path);

        // Check if the file exists
        if (!Storage::exists($file_path)) {
            return 'The file does not exist: ' . $file_path;
        }

        // Get the file content
        $fileContent = Storage::get($file_path);

        render(view('tool', [
            'name' => 'UpdateFile: ' . $file_path,
            'output' => 'Updating content in the file....',
        ]));

        try {
            // Loop through the objects and apply the changes
            foreach ($replace_objects as $object) {
                if (isset($object['find']) && isset($object['replace'])) {
                    $occurrence = isset($object['occurrence']) ? (int)$object['occurrence'] : 1;
                    // Replace the text in the file content
                    $fileContent = $this->strReplaceNthOccurrence($object['find'], $object['replace'], $fileContent, $occurrence);
                } else {
                    return 'Invalid replace object format. Each object must contain "find" and "replace" keys.';
                }
            }
        } catch (\Exception $e) {
            render(view('tool', [
                'name' => 'UpdateFile: ' . $file_path,
                'output' => 'Error updating the file: ' . $e->getMessage(),
            ]));
            return 'Error updating the file: ' . $e->getMessage();
        }

        // Update the file with the new content
        Storage::put($file_path, $fileContent);

        return 'The file has been updated successfully at ' . $file_path . '!';
    }

    private function strReplaceNthOccurrence(string $search, string $replace, string $subject, int $occurrence): string
    {
        $pos = -1;
        for ($i = 0; $i < $occurrence; $i++) {
            $pos = strpos($subject, $search, $pos + 1);
            if ($pos === false) {
                // If the search string is not found enough times, return the subject unchanged
                return $subject;
            }
        }

        return substr_replace($subject, $replace, $pos, strlen($search));
    }
}
