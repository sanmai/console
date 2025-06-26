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

namespace Tests\ConsoleApp;

use Composer\Autoload\ClassLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ConsoleApp\ClassmapCommandProvider;
use Tests\ConsoleApp\Fixtures\HelloCommand;
use Tests\ConsoleApp\Fixtures\CommandWithRequiredArgsCommand;
use Tests\ConsoleApp\Fixtures\CommandWithOptionalArgsCommand;
use Tests\ConsoleApp\Fixtures\CommandWithMixedArgsCommand;
use ReflectionClass;

use function iterator_to_array;
use function Pipeline\take;
use function array_map;
use function get_class;

#[CoversClass(ClassmapCommandProvider::class)]
class ClassmapCommandProviderTest extends TestCase
{
    public function testItMemoizes(): void
    {
        $loader = $this->createMock(ClassLoader::class);

        $loader->expects($this->once())
            ->method('getClassMap')
            ->willReturn([]);

        $provider = new ClassmapCommandProvider($loader);

        $this->assertCount(0, $provider);
        $this->assertCount(0, $provider);
    }

    public function testItFindsCommand(): void
    {
        $loader = $this->createMock(ClassLoader::class);

        $loader->expects($this->once())
            ->method('getClassMap')
            ->willReturn(self::classNamesToPaths(HelloCommand::class));

        $provider = new ClassmapCommandProvider($loader);

        $this->assertInstanceOf(HelloCommand::class, iterator_to_array($provider)[0]);
    }

    public function testItIgnoresNonCommandClasses(): void
    {
        $loader = $this->createMock(ClassLoader::class);

        $loader->expects($this->once())
            ->method('getClassMap')
            ->willReturn(self::classNamesToPaths(
                self::class,
                ClassLoader::class,
            ));

        $provider = new ClassmapCommandProvider($loader);

        $this->assertCount(0, iterator_to_array($provider));
    }

    public function testItIgnoresCommandsWithRequiredConstructorArguments(): void
    {
        $loader = $this->createMock(ClassLoader::class);

        $loader->expects($this->once())
            ->method('getClassMap')
            ->willReturn(self::classNamesToPaths(
                CommandWithRequiredArgsCommand::class,
                CommandWithMixedArgsCommand::class,
            ));

        $provider = new ClassmapCommandProvider($loader);

        $this->assertCount(0, iterator_to_array($provider));
    }

    public function testItAcceptsCommandsWithOptionalConstructorArguments(): void
    {
        $loader = $this->createMock(ClassLoader::class);

        $loader->expects($this->once())
            ->method('getClassMap')
            ->willReturn(self::classNamesToPaths(CommandWithOptionalArgsCommand::class));

        $provider = new ClassmapCommandProvider($loader);

        $commands = iterator_to_array($provider);
        $this->assertCount(1, $commands);
        $this->assertInstanceOf(CommandWithOptionalArgsCommand::class, $commands[0]);
    }

    public function testItFiltersCommandsWithMixedConstructorArguments(): void
    {
        $loader = $this->createMock(ClassLoader::class);

        $loader->expects($this->once())
            ->method('getClassMap')
            ->willReturn(self::classNamesToPaths(
                HelloCommand::class,
                CommandWithOptionalArgsCommand::class,
                CommandWithRequiredArgsCommand::class,
                CommandWithMixedArgsCommand::class,
            ));

        $provider = new ClassmapCommandProvider($loader);

        $commands = iterator_to_array($provider);
        $this->assertCount(2, $commands);

        $commandClasses = array_map(fn($cmd) => get_class($cmd), $commands);
        $this->assertContains(HelloCommand::class, $commandClasses);
        $this->assertContains(CommandWithOptionalArgsCommand::class, $commandClasses);
        $this->assertNotContains(CommandWithRequiredArgsCommand::class, $commandClasses);
        $this->assertNotContains(CommandWithMixedArgsCommand::class, $commandClasses);
    }

    private static function classNamesToPaths(string ...$classNames): array
    {
        return take($classNames)
            ->map(static fn(string $className) => yield $className => (new ReflectionClass($className))->getFileName())
            ->toAssoc();
    }
}
