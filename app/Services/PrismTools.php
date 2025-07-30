<?php

namespace App\Services;

use App\Services\FileTreeLister;
use Prism\Prism\Facades\Tool;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;

use function Termwind\render;

class PrismTools
{
    /**
     * Create the ListFiles tool for Prism
     */
    public static function createListFilesTool(): \Prism\Prism\Tool
    {
        return Tool::as('list_files')
            ->for('List all files and sub directories in the specified path. Use this when you need to list all files and directories.')
            ->withStringParameter('path', 'Directory name to list files from. Default is the base path.')
            ->using(function (string $path): string {
                try {
                    $fileTreeLister = new FileTreeLister();
                    $list = $fileTreeLister->listTree($path);
                    
                    render(view('tool', [
                        'name' => 'ListFiles from ' . $path,
                        'output' => $list,
                    ]));
                    
                    return $list;
                } catch (DirectoryNotFoundException $e) {
                    return $e->getMessage();
                }
            });
    }
    
    /**
     * Get all available Prism tools
     */
    public static function getAllTools(): array
    {
        return [
            self::createListFilesTool(),
        ];
    }
}