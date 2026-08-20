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

3. Container (optional):
   - Loads the [PSR-11](https://www.php-fig.org/psr/psr-11/) container specified in `extra.console.container`
   - Creates discovered commands and providers with `$container->get($class)` instead of `new $class()`
   - Lets commands and providers have constructor dependencies

## The Problem It Solves

I found myself writing [the same console bootstrap script](https://symfony.com/doc/current/components/console.html#creating-a-console-application) over and over. Even with Symfony's command discovery features, you still had to write a lot of boilerplate code.

With `sanmai/console`, you get a ready-made `vendor/bin/console` executable [installed via Composer](https://packagist.org/packages/sanmai/console). No files to create, no permissions to set - just install the package and `vendor/bin/console` is ready to use. It even works in legacy projects that never had Symfony Console commands before.

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
- Must have a no-argument constructor, unless a [container](#container-configuration) is configured

An optimized autoloader is not required to use the custom provider.

### Container Configuration

To create commands and providers with a dependency injection container, specify a class implementing `Psr\Container\ContainerInterface`:

```json
{
    "extra": {
        "console": {
            "container": "App\\Container"
        }
    }
}
```

Or using Composer command:

```bash
composer config extra.console.container 'App\Container'
```

The container:

- Is instantiated with a no-argument constructor
- Replaces `new $class()` for every discovered command and provider, and for the custom provider
- Must be able to create a command or provider by its class name, so an autowiring container is the natural fit

A command that the container cannot create is skipped, the same as a command whose constructor throws. No fallback to `new $class()` happens: when a container is configured, the container is the authority.

For example, with [sanmai/di-container](https://github.com/sanmai/di-container):

```bash
composer require sanmai/di-container
composer config extra.console.container 'DIContainer\Container'
```

Now commands with constructor dependencies are discovered and wired automatically:

```php
<?php
// src/Commands/GreetCommand.php
namespace App\Commands;

use App\Greeter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

#[AsCommand(name: 'greet')]
class GreetCommand extends Command
{
    public function __construct(private readonly Greeter $greeter)
    {
        parent::__construct();
    }
}
```

To configure bindings, extend the container class and pass them to the parent constructor.

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

        // ...or return a variety of iterators,
        // ...or implement a full iterator
    }
}
```

`CommandProviderInterface` implementations must have no required arguments in their constructor as they are instantiated automatically, unless a [container](#container-configuration) is configured. With a container, both commands and providers can declare constructor dependencies.

## Troubleshooting

Commands not showing up?

- Run `composer dump-autoload --optimize` (add `--dev` if your commands are in autoload-dev)
- Verify your command class names end with `Command` (e.g., `HelloCommand`, `MigrateCommand`)
- Check that commands extend `Symfony\Component\Console\Command\Command`
- Commands in `vendor/` are ignored by default
- Command providers must have class names ending with `CommandProvider`
- With a container configured, make sure the container can create the command by its class name

## Testing

```bash
make -j -k
```
