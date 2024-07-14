# Features

🤖 Welcome to Dexor Dev! Let's explore the powerful features that will make your development experience smoother and more efficient.

## Multiple AI Service Support

Dexor Dev supports integration with multiple AI services, giving you the flexibility to choose the service and model that best fits your needs. Whether it's OpenAI, or another service, you can seamlessly switch and leverage different AI capabilities within your projects.

## Project-Specific Assistants

- **Create Assistants for Each Project**: Easily create a new assistant for each of your projects using the `--new` parameter.
  ```sh
  dexor --new
  ```

- **Diverse Assistants**: Each assistant can be configured differently with unique models, prompts, and AI services. This means you can have multiple assistants tailored to specific project requirements, all existing simultaneously.

## Local Conversation Storage

All conversations are now stored locally in a SQLite database. This enhancement offers two primary benefits:
- **Cost Efficiency**: Reduces reliance on external API calls, thereby lowering costs.
- **Improved Speed**: Faster access to stored conversations enhances overall performance.

## Key Capabilities

- **Code Analysis**: I can scan through your project files and folders to read and understand your code, making context-aware changes that fit seamlessly into your existing codebase.
  
- **Bug Fixing**: Automatically analyze your code to identify common issues and suggest or apply fixes, saving you time and effort.
  
- **Test Writing**: Generate comprehensive test cases based on your current code structure to help maintain high-quality code.
  
- **File Creation**: Create new files or components adhering to your project's existing patterns and conventions, ensuring consistency across the codebase.

## Latest Enhancements (Updated on 2024-07-07)

- **Assistant Creation**: Ability to create a new assistant for each project using the `--new` parameter.
- **Chat Completions API**: Transitioned from Assistants API to Chat Completions API for improved speed and cost efficiency.
- **Local Storage**: Conversations are now stored locally in a SQLite database. The database, config file, and cache files are stored in the `home directory/.dexor`.

Let's work together to build something amazing!
