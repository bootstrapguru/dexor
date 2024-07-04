<?php

namespace App\Tools;

use App\Attributes\Description;
use Illuminate\Support\Facades\Storage;
use function Termwind\render;

#[Description('Read content from an existing file at the specified path. Use this when you need to read content from a file.')]
final class ReadFile {

    public function handle(
        #[Description('Absolute File path to read content from')]
        string $file_path,
    ): string {

        render('ReadFile before: '. $file_path);

        // Make sure it's a relative path
        if (str_contains($file_path, Storage::path(DIRECTORY_SEPARATOR))) {
            $file_path = str_replace(Storage::path(DIRECTORY_SEPARATOR), '', $file_path);
        }

        if (Storage::exists($file_path)) {
            return Storage::get($file_path);
        }

        return 'The file does not exist in the path';
    }

}
