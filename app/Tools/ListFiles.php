<?php

namespace App\Tools;

use App\Attributes\Description;
use App\Services\FileTreeLister;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;

use function Termwind\render;

#[Description('List all files and sub directories in the specified path. Use this when you need to list all files and directories.')]
final class ListFiles
{
    public function handle(
        #[Description('directory name to list files from. Default is the base path.')]
        string $path,
    ): string {

        try {
            $fileTreeLister = new FileTreeLister();
            $list = $fileTreeLister->listTree($path);
            render(view('tool', [
                'name' => 'ListFiles from '.$path,
                'output' => $list,
            ]));

            return $list;
        } catch (DirectoryNotFoundException $e) {
            return $e->getMessage();
        }
    }
}
