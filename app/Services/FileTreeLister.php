<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;

class FileTreeLister
{
    protected array $exclude = ['storage'];

    public function __construct()
    {
        // Load .gitignore and populate $exclude array
        $this->loadGitignore();
    }

    public function listTree(?string $path = null): string
    {
        $path = $path ?? Storage::path(DIRECTORY_SEPARATOR);

        if (! File::exists($path) || ! File::isDirectory($path)) {
            return "The path {$path} does not exist or is not a directory.";
        }

        $list = $this->listDirectoryContents($path);

        return $list;
    }

    protected function loadGitignore(): void
    {
        $gitignorePath = Storage::path('.gitignore');

        if (File::exists($gitignorePath)) {
            $patterns = File::lines($gitignorePath);

            foreach ($patterns as $pattern) {
                $pattern = trim($pattern);

                // Ignore comments and empty lines
                if (empty($pattern) || str_starts_with($pattern, '#')) {
                    continue;
                }

                // Normalize patterns by removing leading slashes and handling wildcards
                $pattern = ltrim($pattern, DIRECTORY_SEPARATOR);
                $pattern = str_replace('**', '*', $pattern);

                // Add to exclude array
                $this->exclude[] = $pattern;
            }
        }
    }

    protected function listDirectoryContents(string $path, string $prefix = ''): string
    {
        $output = '';

        try {
            $items = File::directories($path);
            $files = File::files($path);
        } catch (DirectoryNotFoundException $e) {
            throw new DirectoryNotFoundException('Error: '.$e->getMessage());
        }

        foreach ($items as $index => $directory) {
            $relativePath = str_replace(Storage::path(DIRECTORY_SEPARATOR), '', $directory);
            $directoryName = basename($directory);

            if ($this->isExcluded($relativePath) || $this->isExcluded($directoryName) || File::isDirectory($directory) && is_link($directory)) {
                continue;
            }

            $output .= $prefix.'├── '.$directoryName.PHP_EOL;
            $output .= $this->listDirectoryContents($directory, $prefix.($index === array_key_last($items) && empty($files) ? '    ' : '│   '));
        }

        foreach ($files as $file) {
            $relativePath = str_replace(Storage::path(DIRECTORY_SEPARATOR), '', $file);
            $fileName = basename($file);

            if ($this->isExcluded($relativePath) || $this->isExcluded($fileName)) {
                continue;
            }

            $output .= $prefix.'├── '.$fileName.PHP_EOL;
        }

        return $output;
    }

    protected function isExcluded(string $path): bool
    {
        foreach ($this->exclude as $pattern) {
            if (fnmatch($pattern, $path)) {
                return true;
            }
        }

        return false;
    }
}
