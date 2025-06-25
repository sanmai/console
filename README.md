# sanmai/console

[![License](https://img.shields.io/github/license/sanmai/console.svg)](LICENSE)
[![PHP Version](https://img.shields.io/packagist/php-v/sanmai/console.svg)](https://packagist.org/packages/sanmai/console)

Zero-configuration console executable that auto-discovers your Symfony Console commands.

## Requirements

- Composer with optimized autoloader (`composer dump-autoload --optimize`)

## Installation

```bash
composer require sanmai/console
composer dump-autoload --optimize
```

> **Why --optimize?** This library discovers commands by scanning Composer's classmap, which requires an optimized autoloader. This is the trade-off for zero-configuration simplicity.

## Quick Start

1. Create a Symfony Console command:

```php
<?php
// src/Commands/HelloCommand.php
namespace App\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'hello',
    description: 'Says hello'
)]
class HelloCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Hello, World!');
        return Command::SUCCESS;
    }
}
```

2. Update the autoloader:

```bash
composer dump-autoload --optimize
```

3. Run your command:

```bash
vendor/bin/console hello
```

That's it! No configuration files, no manual command registration. Just create command files and they're automatically discovered.

## How It Works

This library provides a ready-made `vendor/bin/console` executable that automatically discovers all Symfony Console commands in your project by:

1. Scanning Composer's optimized classmap
2. Finding classes that end with `Command.php`
3. Loading those that extend `Symfony\Component\Console\Command\Command`
4. Filtering out vendored files

## The Problem It Solves

Even with Symfony's built-in command discovery, you still need to:
1. Create a console executable file (e.g., `bin/console`)
2. Set up the Application instance
3. Configure command discovery
4. Make the file executable

With `sanmai/console`, you get a ready-made `vendor/bin/console` executable installed via Composer. No files to create, no permissions to set - just install the package and `vendor/bin/console` is ready to use.

## Troubleshooting

**Commands not showing up?**
- Ensure you ran `composer dump-autoload --optimize`
- Verify your command files end with `Command.php`
- Check that commands extend `Symfony\Component\Console\Command\Command`
- Commands in `vendor/` are ignored by design

## Testing

```bash
vendor/bin/phpunit
```
