<?php

namespace App\Tools;

use App\Attributes\Description;
use Illuminate\Support\Facades\Storage;
use function Laravel\Prompts\info;

#[Description('Read content from an existing file at the specified path. Use this when you need to read content from a file.')]
final class ReadFile {

    public function handle(
        #[Description('File path to read content from')]
        string $file_path,
    ): string {

        $basePath = Storage::path($file_path);

        if (Storage::exists($basePath)) {
            info('Reading file content from ' . $basePath);
            // Get the file content
            return Storage::get($basePath);
        }

        return 'The file does not exist in the path';
    }

}
