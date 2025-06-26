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
use Tests\ConsoleApp\Fixtures\RequiredArgsCommand;
use ReflectionClass;

use function iterator_to_array;
use function Pipeline\take;

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
        $loader = $this->configureClassLoaderWith(
            HelloCommand::class,
        );

        $provider = new ClassmapCommandProvider($loader);

        $this->assertInstanceOf(HelloCommand::class, iterator_to_array($provider)[0]);
    }

    public function testItIgnoresNonCommandClasses(): void
    {
        $loader = $this->configureClassLoaderWith(
            self::class,
            ClassLoader::class,
        );

        $provider = new ClassmapCommandProvider($loader);

        $this->assertCount(0, iterator_to_array($provider));
    }

    public function testItIgnoresCommandsWithRequiredConstructorArguments(): void
    {
        $loader = $this->configureClassLoaderWith(RequiredArgsCommand::class);

        $provider = new ClassmapCommandProvider($loader);

        $this->assertCount(0, iterator_to_array($provider));
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
