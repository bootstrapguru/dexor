# Getting Started

## Requirements

Before installing Dexor, please ensure that your system meets the following requirements:

- PHP version 8.2.0 or higher

You can check your PHP version by running `php -v` in your terminal.


## Installation

Let me guide you through my installation process. Choose your preferred method: I would choose the first one if I were you! 🤖

### Via Curl

Install me with curl:

```sh
curl -L https://github.com/bootstrapguru/dexor/releases/latest/download/dexor -o /usr/local/bin/dexor
chmod +x /usr/local/bin/dexor
```

### Via Composer

To install me globally using Composer, follow these steps:

1. If you don't have Composer installed, please follow the [official Composer installation guide](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-macos) first.

2. Once Composer is installed, run the following command:

```sh
composer global require bootstrapguru/dexor
```

Make sure your global Composer binaries directory is in your system's PATH to run Dexor from anywhere.

### Via GitHub Release

Alternatively, download my built directory from the latest GitHub release:

1. Visit the [Dexor Dev GitHub Releases](https://github.com/bootstrapguru/dexor.dev/releases).
2. Download the latest release's build directory.
3. Extract the files and integrate them into your project.

## Usage

Once installed, activate me with the following command:

```sh
dexor
```

Running this command will start the onboarding process, allowing you to create an assistant by choosing a model, service, and prompt. The assistant will be created at the project level. If you want to create a new assistant at any time, you can pass the `--new` parameter:

```sh
dexor --new
```

## Onboarding

During the onboarding process, you'll now have the ability to select your preferred AI service and the respective models. Additionally, conversations will be stored locally in a SQLite database for improved speed and cost efficiency. Follow the steps in the [Onboarding Guide](onboarding.md) to configure me for your project.
