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

> Why `--optimize`? Command discovery uses Composer's classmap, which requires an optimized autoloader.

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
    // Avoid side effects in constructors - commands are instantiated during discovery.

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

That's it! No configuration files, no manual command registration.

## How It Works

This library provides a ready-made `vendor/bin/console` executable that automatically discovers all Symfony Console commands in your project by:

1. Scanning Composer's optimized classmap
2. Finding classes that end with `Command.php`
3. Loading those that extend `Symfony\Component\Console\Command\Command`
4. Filtering out vendored files
5. Instantiating each command (commands that throw exceptions or errors are skipped)
6. Discovering `CommandProviderInterface` implementations for commands with dependencies

## The Problem It Solves

Even with Symfony's built-in command discovery, you still need to:
1. Create a console executable file (e.g., `bin/console`)
2. Set up the Application instance
3. Configure command discovery
4. Make the file executable

With `sanmai/console`, you get a ready-made `vendor/bin/console` executable installed via Composer. No files to create, no permissions to set - just install the package and `vendor/bin/console` is ready to use.

## Bootstrap Configuration

Configure a custom bootstrap script in your `composer.json`:

```json
{
    "extra": {
        "console": {
            "bootstrap": "app/bootstrap.php"
        }
    }
}
```

The bootstrap script runs after Composer's autoloader is initialized. Including `vendor/autoload.php` again is safe - the library handles this gracefully.

Example bootstrap script:

```php
<?php
// bootstrap.php

// Set up error handlers, load environment variables, configure services
define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');

// Composer autoloader is already loaded
// Safe to include vendor/autoload.php if needed
```

## Troubleshooting

Commands not showing up?
- Run `composer dump-autoload --optimize` (add `--dev` if your commands are in autoload-dev)
- Verify your command files end with `Command.php`
- Check that commands extend `Symfony\Component\Console\Command\Command`
- Commands in `vendor/` are ignored by default
- Commands with required constructor arguments are filtered out

### Commands with Dependencies

For commands that require constructor dependencies, implement the `CommandProviderInterface`:

```php
<?php
// src/MyCommandProvider.php
namespace App;

use ConsoleApp\CommandProviderInterface;
use IteratorAggregate;
use Symfony\Component\Console\Command\Command;

class MyCommandProvider implements CommandProviderInterface, IteratorAggregate
{
    // Provider must have a no-required-argument constructor
    public function __construct(int $optional = 0) {}

    public function getIterator(): \Traversable
    {
        // Build your services/dependencies
        $database = new DatabaseConnection();
        $cache = new CacheService();

        // Yield commands with their dependencies
        yield new DatabaseMigrationCommand($database);
        yield new CacheClearCommand($cache);
    }
}
```

`CommandProviderInterface` implementations must have no required arguments in their constructor as they are instantiated automatically.

## Testing

```bash
make -j -k
```
