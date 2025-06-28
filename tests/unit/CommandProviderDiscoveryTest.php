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
use ConsoleApp\CommandProviderDiscovery;
use Tests\ConsoleApp\Fixtures\TestCommandProvider;
use Tests\ConsoleApp\Fixtures\HelloCommand;
use Tests\ConsoleApp\Fixtures\OptionalArgsCommand;

use function iterator_to_array;

#[CoversClass(CommandProviderDiscovery::class)]
class CommandProviderDiscoveryTest extends TestCase
{
    public function testItDiscoversProviders(): void
    {
        $loader = $this->createMock(ClassLoader::class);

        $loader->expects($this->once())
            ->method('getClassMap')
            ->willReturn([
                TestCommandProvider::class => __DIR__ . '/Fixtures/TestCommandProvider.php',
                HelloCommand::class => __DIR__ . '/Fixtures/HelloCommand.php',
            ]);

        $provider = new CommandProviderDiscovery($loader);

        $commands = iterator_to_array($provider, false);

        // Should have 2 commands from TestCommandProvider
        $this->assertCount(2, $commands);
        $this->assertInstanceOf(HelloCommand::class, $commands[0]);
        $this->assertInstanceOf(OptionalArgsCommand::class, $commands[1]);
    }

    public function testItFiltersOutNonProviders(): void
    {
        $loader = $this->createMock(ClassLoader::class);

        $loader->expects($this->once())
            ->method('getClassMap')
            ->willReturn([
                HelloCommand::class => __DIR__ . '/Fixtures/HelloCommand.php',
            ]);

        $provider = new CommandProviderDiscovery($loader);

        $commands = iterator_to_array($provider);

        // Should have no commands as HelloCommand is not a provider
        $this->assertCount(0, $commands);
    }
}
