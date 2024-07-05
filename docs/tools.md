# Available Tools

1. **List Files**: Lists all files and subdirectories in a specified path.

2. **Read File**: Reads content from an existing file at the specified path.

3. **Update File**: Updates the content of an existing file at the specified path.

4. **Write To File**: Writes content to an existing file.

# Custom Tools

To add a custom tool, follow these steps:

1. Create a new PHP file for the custom tool in the Tools directory.
2. Implement the functionality of the custom tool in the PHP file.
3. Use the tool as needed in the project.

Sample PHP Code:

```php
<?php

namespace App\Tools;

use App\Attributes\Description;
use function Termwind\render;

#[Description('List all files and sub directories in the specified path. Use this when you need to list all files and directories.')]
final class ListFiles
{
    public function handle(
        #[Description('directory name to list files from. Default is the base path.')]
        string $path,
    ): string {

        // Your custom logic here
    }
}

?>
```
## Register the Custom Tool

To use the custom tool in ChatAssistant, follow these steps:

1. Import the custom tool file inside the `ChatAssistant` file.
2. Pass the custom tool through `RegisterTool` code in the constructor of `ChatAssistant`.

```php
// Import the custom tool file inside ChatAssistant
use App\Tools\ListFiles;

class ChatAssistant {
    // Other code
    public function __construct()
    {
        $this->client = OpenAI::client(config('droid.api_key'));

        // register the tools
        $this->register([
            WriteToFile::class,
            UpdateFile::class,
            ListFiles::class,
            ReadFile::class,
            // Add your custom tool here
            CustomTool::class,
        ]);
    }
}

```
