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

use ConsoleApp\ClassmapCommandProvider;
use ConsoleApp\CommandProviderDiscovery;
use ConsoleApp\CommandProviderProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use TestProviderApp\ConsoleProvider;
use Tests\ConsoleApp\Fixtures\TestCommandProvider;
use Tests\ConsoleApp\Fixtures\HelloCommand;
use Tests\ConsoleApp\Fixtures\OptionalArgsCommand;
use ConsoleApp\ConfigLoader;
use Composer\Autoload\ClassLoader;

use function iterator_to_array;

#[CoversClass(CommandProviderProvider::class)]
final class CommandProviderProviderTest extends TestCase
{
    public function testItProvides(): void
    {
        $provider = new CommandProviderProvider(new TestCommandProvider(), new TestCommandProvider());

        $commands = iterator_to_array($provider, false);

        $this->assertCount(4, $commands);
        $this->assertInstanceOf(HelloCommand::class, $commands[0]);
        $this->assertInstanceOf(OptionalArgsCommand::class, $commands[1]);
        $this->assertInstanceOf(HelloCommand::class, $commands[2]);
    }

    public function testItBuildsDefaultProviders(): void
    {
        $classLoader = $this->createMock(ClassLoader::class);
        $configLoader = $this->createMock(ConfigLoader::class);

        $configLoader->expects($this->once())
            ->method('getProviderClasses')
            ->willReturn([]);

        $result = [...CommandProviderProvider::defaultProviders($configLoader, $classLoader)];

        $this->assertCount(2, $result);
        $this->assertInstanceOf(ClassmapCommandProvider::class, $result[0]);
        $this->assertInstanceOf(CommandProviderDiscovery::class, $result[1]);
    }

    public function testItBuildsDefaultAndCustomProviders(): void
    {
        $classLoader = $this->createMock(ClassLoader::class);
        $configLoader = $this->createMock(ConfigLoader::class);

        $configLoader->expects($this->once())
            ->method('getProviderClasses')
            ->willReturn([ConsoleProvider::class]);

        $result = [...CommandProviderProvider::defaultProviders($configLoader, $classLoader)];

        $this->assertCount(3, $result);
        $this->assertInstanceOf(ClassmapCommandProvider::class, $result[0]);
        $this->assertInstanceOf(CommandProviderDiscovery::class, $result[1]);
        $this->assertInstanceOf(ConsoleProvider::class, $result[2]);
    }

    public function testItBuildsDefaultAndMultipleCustomProviders(): void
    {
        $classLoader = $this->createMock(ClassLoader::class);
        $configLoader = $this->createMock(ConfigLoader::class);

        $configLoader->expects($this->once())
            ->method('getProviderClasses')
            ->willReturn([
                ConsoleProvider::class,
                ConsoleProvider::class,
            ]);

        $result = [...CommandProviderProvider::defaultProviders($configLoader, $classLoader)];

        $this->assertCount(4, $result);
        $this->assertInstanceOf(ConsoleProvider::class, $result[3]);
    }
}
