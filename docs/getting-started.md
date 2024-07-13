# Getting Started

## Installation

Let me guide you through my installation process. Choose your preferred method: I would choose the first one if I were you! 🤖

### Via Curl

Install me with curl:

```sh
curl -L https://github.com/bootstrapguru/droid.dev/releases/latest/download/droid -o /usr/local/bin/droid
chmod +x /usr/local/bin/droid
```

### Via Composer

To install me globally using Composer, run:

```sh
composer global require droid
```

### Via GitHub Release

Alternatively, download my built directory from the latest GitHub release:

1. Visit the [Droid Dev GitHub Releases](https://github.com/bootstrapguru/droid.dev/releases).
2. Download the latest release's build directory.
3. Extract the files and integrate them into your project.

## Usage

Once installed, activate me with the following command:

```sh
droid
```

I will display all my available commands and options. Dive into the documentation to explore my full capabilities and features.

## Creating a New Assistant

To create a new assistant for your project, use the `--new` parameter with the `droid` command:

```sh
droid --new
```

This command will guide you through the process of setting up a new assistant for your project.

## Onboarding

During the onboarding process, you'll now have the ability to select your preferred AI service and the respective models. Additionally, conversations will be stored locally in a SQLite database for improved speed and cost efficiency. Follow the steps in the [Onboarding Guide](onboarding.md) to configure me for your project.