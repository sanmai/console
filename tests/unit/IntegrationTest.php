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
use ConsoleApp\ClassmapCommandProvider;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Tests\ConsoleApp\Fixtures\AbstractCommand;
use Tests\ConsoleApp\Fixtures\HelloCommand;
use Tests\ConsoleApp\Fixtures\OptionalArgsCommand;
use Tests\ConsoleApp\Fixtures\RequiredArgsCommand;
use ReflectionClass;
use Tests\ConsoleApp\Fixtures\WithoutConstructor;

use function iterator_to_array;
use function Pipeline\take;

#[CoversNothing]
final class IntegrationTest extends TestCase
{
    public function testItFindsCommand(): void
    {
        $loader = $this->configureClassLoaderWith(
            RequiredArgsCommand::class,
            AbstractCommand::class,
            OptionalArgsCommand::class,
            HelloCommand::class,
            WithoutConstructor::class,
        );

        $provider = new ClassmapCommandProvider($loader);

        $commands = iterator_to_array($provider, false);

        $this->assertCount(2, $commands);

        $this->assertInstanceOf(OptionalArgsCommand::class, $commands[0]);
        $this->assertInstanceOf(HelloCommand::class, $commands[1]);
    }

    private function configureClassLoaderWith(string ...$classNames): ClassLoader
    {
        $namesPaths = take($classNames)
            ->map(static fn(string $className) => yield $className => (new ReflectionClass($className))->getFileName())
            ->toAssoc();

        $loader = $this->createMock(ClassLoader::class);

        $loader->expects($this->once())
            ->method('getClassMap')
            ->willReturn($namesPaths);

        return $loader;
    }
}
