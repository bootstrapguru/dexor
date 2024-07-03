<?php

namespace App\Tools;

use App\Attributes\Description;
use App\Services\FileTreeLister;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use function Termwind\render;

#[Description('List all files and sub directories in the specified path. Use this when you need to list all files and directories.')]
final class ListFiles {

    public function handle(
        #[Description('directory name to list files from')]
        string $path,
    ): string {

        $basePath = base_path($path);

        render(<<<HTML
            <div class="py-1 ml-2">
                <div class="px-1 bg-blue-300 text-black">Listing Files from</div>
                🗂️ $basePath
            </div>
        HTML);

        try {
            $fileTreeLister = new FileTreeLister();
            return $fileTreeLister->listTree($basePath);
        } catch (DirectoryNotFoundException $e) {
            return $e->getMessage();
        }
    }
}
