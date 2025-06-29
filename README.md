# sanmai/console

[![License](https://img.shields.io/github/license/sanmai/console.svg)](LICENSE)
[![PHP Version](https://img.shields.io/packagist/php-v/sanmai/console.svg)](https://packagist.org/packages/sanmai/console)

Zero-configuration console executable that auto-discovers your [Symfony Console](https://github.com/symfony/console) commands.

## Installation

```bash
composer require sanmai/console
```

### For Auto-Discovery

If you want commands to be discovered automatically:

```bash
composer dump-autoload --optimize
```

> Auto-discovery uses Composer's classmap, which requires an [optimized autoloader](https://getcomposer.org/doc/articles/autoloader-optimization.md).

### For PSR-4 Projects

If you prefer explicit command registration without optimization, configure a [custom provider](#custom-provider-configuration).

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

This library provides a ready-made `vendor/bin/console` executable that automatically discovers all Symfony Console commands in your project:

1. Auto-discovery (requires an optimized autoloader):
   - Scans Composer's classmap for classes extending `Command` with names ending in `Command`
   - Finds `CommandProviderInterface` implementations with names ending in `CommandProvider`
   - Filters out vendor files
   - Skips classes that throw exceptions during instantiation

2. Custom provider (optional, no optimization needed):
   - Loads the provider specified in `extra.console.provider`
   - Provider returns command instances via its iterator
   - Works alongside auto-discovery

## The Problem It Solves

Even with Symfony's built-in command discovery, you still need to:

1. Create a console executable file (e.g., `bin/console`)
2. Set up the Application instance
3. Configure command discovery
4. Make the file executable

With `sanmai/console`, you get a ready-made `vendor/bin/console` executable [installed via Composer](https://packagist.org/packages/sanmai/console). No files to create, no permissions to set - just install the package and `vendor/bin/console` is ready to use.

## Configuration

### Bootstrap Scripts

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

Or use Composer commands:

```bash
# Configure bootstrap script
composer config extra.console.bootstrap app/bootstrap.php

# Enable optimized autoloader (required for command discovery)
composer config optimize-autoloader true
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

### Custom Provider Configuration

In addition to auto-discovery, you can specify a custom command provider:

```json
{
    "extra": {
        "console": {
            "provider": "App\\Console\\CommandProvider"
        }
    }
}
```

Or using Composer command:

```bash
composer config extra.console.provider 'App\Console\CommandProvider'
```

The custom provider:

- Works alongside auto-discovery (doesn't replace it)
- Doesn't need the `CommandProvider` suffix
- Must implement `CommandProviderInterface`
- Must have a no-argument constructor

An optimized autoloader is not required to use the custom provider.

## Commands with Dependencies

For commands that require constructor dependencies, implement the `CommandProviderInterface`:

```php
<?php
// src/DatabaseCommandProvider.php
namespace App;

use ConsoleApp\CommandProviderInterface;
use IteratorAggregate;
use Symfony\Component\Console\Command\Command;

class DatabaseCommandProvider implements CommandProviderInterface, IteratorAggregate
{
    // Provider must have a no-required-argument constructor
    public function __construct(int $optional = 0) {}

    public function getIterator(): \Traversable
    {
        // Build your services/dependencies
        $database = new DatabaseConnection();
        $cache = new CacheService();

        // You can yield commands one by one...
        yield new DatabaseMigrationCommand($database);
        yield new CacheClearCommand($cache);

        // ...or return an array iterator
        // return new ArrayIterator([
        //     new DatabaseMigrationCommand($database),
        //     new CacheClearCommand($cache),
        // ]);
    }
}
```

`CommandProviderInterface` implementations must have no required arguments in their constructor as they are instantiated automatically.

## Troubleshooting

Commands not showing up?

- Run `composer dump-autoload --optimize` (add `--dev` if your commands are in autoload-dev)
- Verify your command class names end with `Command` (e.g., `HelloCommand`, `MigrateCommand`)
- Check that commands extend `Symfony\Component\Console\Command\Command`
- Commands in `vendor/` are ignored by default
- Command providers must have class names ending with `CommandProvider`

## Testing

```bash
make -j -k
```
