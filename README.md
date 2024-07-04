# Droid

Welcome to **Droid**, a powerful command-line tool built on top of the [Laravel Zero](https://laravel-zero.com/) micro-framework. Droid is designed to simplify and automate various development tasks.

<p align="center">
    <img title="Droid Logo" height="100" src="path_to_droid_logo" alt="Droid Logo" />
</p>

<p align="center">
  <a href="https://github.com/bootstrapguru/droid/actions"><img src="https://github.com/bootstrapguru/droid/actions/workflows/tests.yml/badge.svg" alt="Build Status" /></a>
  <a href="https://packagist.org/packages/bootstrapguru/droid"><img src="https://img.shields.io/packagist/dt/bootstrapguru/droid.svg" alt="Total Downloads" /></a>
  <a href="https://packagist.org/packages/bootstrapguru/droid"><img src="https://img.shields.io/packagist/v/bootstrapguru/droid.svg?label=stable" alt="Latest Stable Version" /></a>
  <a href="https://packagist.org/packages/bootstrapguru/droid"><img src="https://img.shields.io/packagist/l/bootstrapguru/droid.svg" alt="License" /></a>
</p>

## Features

- Built on top of the [Laravel](https://laravel.com) components.
- Supports various tasks for efficient development.
- Provides a clean and user-friendly command-line interface.
- Easy to extend and customize for your needs.

## Installation

### Using Composer

You can install Droid globally using Composer:

```bash
composer global require bootstrapguru/droid
```

Make sure to place the `~/.composer/vendor/bin` directory (or the equivalent directory for your OS) in your PATH so the `droid` executable can be located by your system.

### Download Standalone Build

Alternatively, you can download the standalone `droid` file from the [builds](builds) directory.

```bash
curl -o droid https://droid.dev/builds/droid
chmod +x droid
mv droid /usr/local/bin/droid
```

## Usage

Once installed, you can start using Droid by running:

```bash
droid
```

This will display a list of available commands and options. For more detailed command usage, you can append the `--help` flag to any command:

## Documentation

For full documentation and advanced usage, please visit [droid.dev](https://droid.dev/).

## Support the development

**Do you like Droid? Support its development by donating**

- PayPal: [Donate](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=66BYDWAT92N6L)
- Patreon: [Donate](https://www.patreon.com/droid)

## License

Droid is an open-source software licensed under the MIT license.
