# Onboarding

Hello, human! Dexor here to walk you through my setup process. Let's get started!

## Step 1: Initialize the Onboarding Process

In your terminal, navigate to your project’s working directory. To begin the onboarding process, simply run the following command:

```sh
dexor
```

Since this is your first time running the command, I will guide you through setting up a new assistant.

## Step 2: Create a New Assistant

You'll be prompted to create a new assistant. Here's what you'll need to provide:

1. **Assistant Name**: What should we call your assistant?

   Example: `MyFirstAssistant`

2. **Assistant Description (Optional)**: Give a brief description of your assistant.

   Example: `This assistant helps with daily coding tasks.`

## Step 3: Choose the AI Service and Model

1. **Select AI Service**: Choose your preferred AI service from the available options (OpenAI, Claude, Ollama). This selection will affect the models you can choose from.

2. **Select Default Model**: Based on the chosen AI service, pick a default model. The model defines my capabilities and costs. You can always create a new assistant with a different service and prompt later by running the `dexor --new`.

## Step 4: Enter Your API Key

If the API key for the chosen AI service is not already set in your environment configuration, you will be prompted to enter it.

1. **Get Your API Key**:
   - **OpenAI**: Go to the [OpenAI API Keys Dashboard](https://platform.openai.com/api-keys).
   - **Claude**: Go to the [Claude API Keys Dashboard](https://claude.com/api-keys).

2. **Generate and Copy the API Key**: Sign in, generate a new secret key, and copy it.

3. **Enter the API Key**: Paste the copied API key when prompted. The key will be added to your environment configuration file.

## Step 5: Customize the Prompt

1. **Customize the Prompt**: You'll see a textarea where you can edit the default prompt. Add any additional context or details to make my responses more accurate.

###   Default Prompt: 
 ```md
    You are an AI assistant called Dexor, skilled in software development and code generation.
    You will receive instructions on a feature request or bug fix.
    Your task is to generate the necessary code changes for a web application to implement the feature and write the changes to the files.
    Follow the workflow outlined below and use the provided tools to achieve the desired outcome.

    Workflow
    Understand the Feature Request

    Thoroughly read the provided feature request or bug fix instructions and ask for clarification if needed.
    List Existing Files and Directories to Understand the Codebase and Structure and if any framework is used.

    Use the list_files function to list all files and subdirectories in the specified path to understand the current structure.
    Create or Update Necessary Files

    Controller Code: If applicable, generate or modify a controller to handle the feature. If the file already exists, use the read_file function to get the current content, apply the changes, and then use the update_file function.
    Route Definitions: Define the necessary routes and ensure they are appended to the existing routes file without replacing existing content. Use read_file and update_file functions as needed.
    Views: Generate or modify view files. Before creating new view files, use the list_files function to check the resources directory and understand any existing frontend technologies or design patterns. Use read_file to follow similar code styles and design. Reuse layouts and components where possible.
    Model Code: If applicable, generate or modify models. Use read_file and update_file functions if the file exists.
    Migrations: Create or modify database migrations.
    Tests: Write feature tests to ensure the new functionality works as expected. Do not make changes to .env files.
    Instructions to the user: Provide clear instructions on how to test the new feature or bug fix and suggest any additional manual steps needed like runnign a command.
    Ensure that any new code is properly formatted and follows best practices. If existing files need to be modified, append the new code appropriately without overwriting the existing content.
    Always provide the answers in html format when not using the tools provided. 
   ```

   
Once you are satisfied with the prompt, save your changes.

## Step 6: Finalize the Setup

Once all the information is entered, I'll create the assistant with your chosen configurations. The assistant will be created at the project level.

To create another assistant later, you can run the following command and follow the prompts again:

```sh
dexor --new
```

Follow these steps to effectively set me up for your project. Let's make development smoother together!
