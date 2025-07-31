# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Dexor is an AI-powered CLI development assistant built with Laravel Zero (PHP 8.2+). It provides a command-line interface for interacting with various AI providers through the PrismPHP library to assist with development tasks including code analysis, bug fixing, test writing, and file creation.

## Key Features

- **Code Analysis**: Scans and understands existing codebase structure
- **Bug Fixing**: Automatically identifies and fixes bugs
- **Test Writing**: Generates comprehensive test cases
- **File Operations**: Create, read, update files following existing patterns
- **Command Execution**: Run terminal commands through AI assistance
- **Project-Specific Assistants**: Create unique assistants per project with custom prompts
- **Multiple AI Providers**: Unified interface through PrismPHP for OpenAI, Claude (Anthropic), Ollama, OpenRouter, DeepSeek, Gemini, Groq, Mistral, and xAI
- **Local Conversation Storage**: SQLite database for cost efficiency and history

## Development Commands

```bash
# Run tests
./vendor/bin/pest

# Build PHAR executable
php dexor app:build

# Documentation development
npm run docs:dev      # Start dev server
npm run docs:build    # Build documentation
npm run docs:preview  # Preview built docs

# Run locally
php dexor           # Run the CLI tool
php dexor --new     # Create a new assistant
```

## Architecture Overview

### Core Structure
- **app/Commands/**: CLI command entry points - the main command is `DexorCommand.php`
- **app/Services/PrismAdapter.php**: Adapter service that interfaces with PrismPHP for all AI providers
- **app/Services/ToolAdapter.php**: Converts Dexor's tool system to PrismPHP's tool format
- **app/Services/ChatAssistant.php**: Central service that manages conversations and coordinates with AI providers through PrismPHP
- **app/Tools/**: Function calling implementations:
  - `ListFiles.php`: Lists files and directories
  - `ReadFile.php`: Reads file content
  - `UpdateFile.php`: Updates existing files
  - `CreateFile.php`: Creates new files
  - `ExecuteCommand.php`: Runs terminal commands
- **app/Models/**: Eloquent models for Assistant, Thread, and Message entities

### Key Patterns
1. **AI Provider Abstraction**: All providers are accessed through PrismPHP's unified interface
2. **Tool System**: Uses Laravel's dependency injection to register tools, then converts them to PrismPHP format
3. **Configuration**: Multi-level config system - global (`config/dexor.php`), PrismPHP providers (`config/prism.php`), and per-assistant settings

### Database
- SQLite database stored in `storage/database.sqlite`
- Migrations in `database/migrations/` define schema for assistants, threads, and messages
- Each project has its own assistant configuration stored in `.dexor` file

## Important Implementation Details

### Adding New AI Providers
1. Check if PrismPHP already supports the provider
2. If not supported by Prism, contribute to the PrismPHP library
3. Update `PrismAdapter::mapServiceToProvider()` to map the service name
4. Add provider configuration to `config/prism.php`
5. Update `PrismAdapter::getModels()` with available models

### Adding New Tools
1. Create tool class in `app/Tools/`
2. Implement required methods: `name()`, `description()`, `parameters()`, `handle()`
3. Use `#[Description]` attributes for parameter documentation
4. Tools are auto-discovered via the `HasTools` trait
5. Register in `ChatAssistant` class if needed

### Testing
- Uses Pest PHP testing framework
- Tests located in `tests/` directory
- Focus on integration tests for AI providers and tool functionality

## Configuration Files
- `config/dexor.php`: Main config (default AI service, system prompts)
- `config/prism.php`: PrismPHP provider configurations and API keys
- `box.json`: PHAR build configuration
- `.env.example`: Environment variable template for API keys
- `.dexor`: Project-specific assistant configuration (created per project)

## Supported AI Models

### Recommended Models
- **Claude 3.5 Sonnet** (Anthropic): Best for advanced reasoning and code understanding
- **GPT-4o** (OpenAI): Strong general-purpose model
- **DeepSeek Coder V2**: Specialized for coding tasks

### All Available Providers (via PrismPHP)
- **Anthropic**: Claude 3.5 Sonnet, Claude 3 Opus, Claude 3 Haiku
- **OpenAI**: GPT-4o, GPT-4o-mini, GPT-4 Turbo, GPT-3.5 Turbo
- **OpenRouter**: Auto-routing to best available model
- **Ollama**: Llama 3.2, Llama 3.1, Llama 3, Mistral, CodeLlama (local)
- **DeepSeek**: DeepSeek Coder V2, DeepSeek Chat
- **Gemini**: Google's Gemini models
- **Groq**: Fast inference for supported models
- **Mistral**: Mistral AI models
- **xAI**: Grok models

## Default System Prompt

The assistant uses a comprehensive prompt that guides it to:
1. Understand feature requests and bug reports thoroughly
2. Analyze existing codebase structure before making changes
3. Create/update all necessary files (controllers, routes, views, models, migrations, tests)
4. Follow existing code patterns and conventions
5. Provide clear testing instructions
6. Format responses in HTML when not using tools