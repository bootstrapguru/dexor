<?php

namespace App\Tools;

use App\Attributes\Description;
use App\Services\FileTreeLister;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;

#[Description('List all files and sub directories in the specified path. Use this when you need to list all files and directories.')]
final class ListFiles {

    public function handle(
        #[Description('directory name to list files from. Default is the base path.')]
        string $path,
    ): string {

        try {
            $fileTreeLister = new FileTreeLister();
            return $fileTreeLister->listTree($path);
        } catch (DirectoryNotFoundException $e) {
            return $e->getMessage();
        }
    }
}
