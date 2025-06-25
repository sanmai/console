<?php

/**
 * Copyright 2025 Alexey Kopytko <alexey@kopytko.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace ConsoleApp;

use Composer\Autoload\ClassLoader;
use IteratorAggregate;
use Later\Interfaces\Deferred;
use Symfony\Component\Console\Command\Command;
use Traversable;

use function is_subclass_of;
use function Later\later;
use function Pipeline\take;
use function str_contains;
use function str_ends_with;

/**
 * Command provider that discovers commands from Composer's classmap
 *
 * @implements IteratorAggregate<Command>
 */
class ClassmapCommandProvider implements IteratorAggregate
{
    /** @var Deferred<iterable<Command>> */
    private readonly Deferred $commands;

    public function __construct(private readonly ClassLoader $classLoader)
    {
        $this->commands = later(fn() => yield $this->listCommands());
    }

    /**
     * @return iterable<Command>
     */
    private function listCommands(): iterable
    {
        return take($this->classLoader->getClassMap())
            ->stream()
            ->cast(realpath(...))
            ->filter(self::isNotVendoredDependency(...))
            ->filter(self::hasCommandInFilename(...))
            ->keys()
            ->filter(self::isCommandSubclass(...))
            ->cast(self::newCommand(...));
    }

    private static function isNotVendoredDependency(string $filename): bool
    {
        return !str_contains($filename, '/vendor/');
    }

    private static function hasCommandInFilename(string $filename): bool
    {
        return str_ends_with($filename, 'Command.php');
    }

    /**
     * @param class-string $class
     */
    private static function isCommandSubclass(string $class): bool
    {
        return is_subclass_of($class, Command::class);
    }

    /**
     * @param class-string<Command> $class
     */
    private static function newCommand(string $class): Command
    {
        return new $class();
    }

    public function getIterator(): Traversable
    {
        return $this->commands->get();
    }

}
