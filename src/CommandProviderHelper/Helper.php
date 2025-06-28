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

namespace ConsoleApp\CommandProviderHelper;

use ConsoleApp\CommandProviderInterface;
use Symfony\Component\Console\Command\Command;
use Throwable;
use Traversable;

use function is_subclass_of;
use function realpath;
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use function strtok;

final class Helper
{
    private readonly string $namespacePrefix;

    public function __construct()
    {
        /** @psalm-suppress PossiblyFalseOperand */
        $this->namespacePrefix = strtok(self::class, '\\') . '\\';
    }

    public function realpath(string $filename): string
    {
        $realpath = realpath($filename);

        if (false === $realpath) {
            return $filename;
        }

        return $realpath;
    }

    public function isNotVendoredDependency(string $filename): bool
    {
        return !str_contains($filename, '/vendor/');
    }

    public function hasCommandInFilename(string $filename): bool
    {
        return str_ends_with($filename, 'Command.php');
    }

    /**
     * @param class-string $class
     */
    public function isCommandSubclass(string $class): bool
    {
        return is_subclass_of($class, Command::class);
    }

    /**
     * @param class-string<Command> $class
     * @psalm-suppress UnsafeInstantiation
     */
    public function newCommand(string $class): ?Command
    {
        try {
            return new $class();
        } catch (Throwable) {
            return null;
        }
    }

    public function isNotOurNamespace(string $class): bool
    {
        return !str_starts_with($class, $this->namespacePrefix);
    }

    /**
     * @param class-string $class
     */
    public function isCommandProviderSubclass(string $class): bool
    {
        return is_subclass_of($class, CommandProviderInterface::class);
    }

    /**
     * @param class-string<CommandProviderInterface> $class
     * @return Traversable<Command>
     * @psalm-suppress UnsafeInstantiation
     */
    public function newCommandProvider(string $class): Traversable
    {
        return new $class();
    }
}
