<?php

namespace App\Services;

use App\Services\FileTreeLister;
use Prism\Prism\Facades\Tool;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

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
     * Create the ReadFile tool for Prism
     */
    public static function createReadFileTool(): \Prism\Prism\Tool
    {
        return Tool::as('read_file')
            ->for('Read content from an existing file at the specified path. Use this when you need to read content from a file.')
            ->withStringParameter('file_path', 'Absolute File path to read content from')
            ->using(function (string $file_path): string {
                // Make sure it's a relative path
                if (str_contains($file_path, Storage::path(DIRECTORY_SEPARATOR))) {
                    $file_path = str_replace(Storage::path(DIRECTORY_SEPARATOR), '', $file_path);
                }

                if (Storage::exists($file_path)) {
                    render(view('tool', [
                        'name' => 'ReadFile',
                        'output' => $file_path,
                    ]));

                    return Storage::get($file_path);
                }

                $output = 'The file does not exist in the path: '.$file_path;
                render(view('tool', [
                    'name' => 'ReadFile',
                    'output' => $output,
                ]));

                return $output;
            });
    }
    
    /**
     * Create the CreateFile tool for Prism
     */
    public static function createCreateFileTool(): \Prism\Prism\Tool
    {
        return Tool::as('create_file')
            ->for('Create content in a new file at the specified path. This tool allows you to create files with initial content.')
            ->withStringParameter('file_path', 'Relative File path to create the file at')
            ->withStringParameter('content', 'Initial content to write to the file')
            ->using(function (string $file_path, string $content): string {
                // Make sure it's a relative path
                if (str_contains($file_path, Storage::path(DIRECTORY_SEPARATOR))) {
                    $file_path = str_replace(Storage::path(DIRECTORY_SEPARATOR), '', $file_path);
                }

                if (Storage::exists($file_path)) {
                    render(view('tool', [
                        'name' => 'CreateFile',
                        'output' => 'The file already exists; please choose a different name or update it instead.',
                    ]));
                    return 'The file already exists: '.$file_path;
                }

                $directory = dirname($file_path);

                // Ensure the directory exists
                if (! Storage::exists($directory)) {
                    Storage::makeDirectory($directory, 0755, true);
                }

                Storage::put($file_path, $content);

                $output = 'Created File: '.$file_path;
                render(view('tool', [
                    'name' => 'CreateFile',
                    'output' => $output,
                ]));

                return $output;
            });
    }
    
    /**
     * Create the UpdateFile tool for Prism
     */
    public static function createUpdateFileTool(): \Prism\Prism\Tool
    {
        return Tool::as('update_file')
            ->for('Update the content of an existing file at the specified path. Use this when you need to update the existing of a file after write_to_file returns a suggestion to merge the content. Expected format for `replace_objects`: [ { "find": "text_to_find", "replace": "replacement_text" }, ... ]')
            ->withStringParameter('file_path', 'File path to write content to')
            ->withStringParameter('replace_objects_json', 'JSON string format of objects containing text to find and text to replace. Each object should have `find` and `replace` keys.')
            ->using(function (string $file_path, string $replace_objects_json): string {
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
                            // Replace the text in the file content
                            $fileContent = str_replace($object['find'], $object['replace'], $fileContent);
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
            });
    }
    
    /**
     * Create the ExecuteCommand tool for Prism
     */
    public static function createExecuteCommandTool(): \Prism\Prism\Tool
    {
        return Tool::as('execute_command')
            ->for('Executes a terminal command and returns the output. Use this when you need to execute a terminal command like git and other framework commands')
            ->withStringParameter('command', 'The command to execute.')
            ->using(function (string $command): string {
                $process = Process::fromShellCommandline($command);

                try {
                    $process->mustRun();

                    $output = $process->getOutput();
                    render(view('tool', [
                        'name' => 'ExecuteCommand: '.$command,
                        'output' => $output,
                    ]));

                    return $output;
                } catch (ProcessFailedException $exception) {
                    $output = 'The command failed: '.$exception->getMessage();
                    render(view('tool', [
                        'name' => 'ExecuteCommand Failed: '.$command,
                        'output' => $output,
                    ]));

                    return $output;
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
            self::createReadFileTool(),
            self::createCreateFileTool(),
            self::createUpdateFileTool(),
            self::createExecuteCommandTool(),
        ];
    }
}