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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ConsoleApp\CommandProviderDiscovery;
use Tests\ConsoleApp\Fixtures\HelloCommand;
use Tests\ConsoleApp\Fixtures\OptionalArgsCommand;
use Tests\ConsoleApp\Fixtures\TestCommandProvider;

use function iterator_to_array;
use function array_map;
use function get_class;
use function array_merge;

#[CoversClass(ClassmapCommandProvider::class)]
#[CoversClass(CommandProviderDiscovery::class)]
class CommandProviderIntegrationTest extends TestCase
{
    public function testItDiscoversCommandsFromProviders(): void
    {
        $loader = $this->createMock(ClassLoader::class);

        // Simulate a classmap with both direct commands and a command provider
        $classMap = [
            HelloCommand::class => __DIR__ . '/Fixtures/HelloCommand.php',
            TestCommandProvider::class => __DIR__ . '/Fixtures/TestCommandProvider.php',
            OptionalArgsCommand::class => __DIR__ . '/Fixtures/OptionalArgsCommand.php',
        ];

        $loader->expects($this->exactly(2))
            ->method('getClassMap')
            ->willReturn($classMap);

        $commandProvider = new ClassmapCommandProvider($loader);
        $providerDiscovery = new CommandProviderDiscovery($loader);

        $directCommands = iterator_to_array($commandProvider, false);
        $providedCommands = iterator_to_array($providerDiscovery, false);
        $allCommands = array_merge($directCommands, $providedCommands);

        // Should have 4 commands total:
        // - HelloCommand (direct)
        // - OptionalArgsCommand (direct)
        // - HelloCommand (from provider)
        // - OptionalArgsCommand (from provider)
        $this->assertCount(2, $directCommands);
        $this->assertCount(2, $providedCommands);
        $this->assertCount(4, $allCommands);

        // Verify we have instances of the expected command types
        $commandTypes = array_map(fn($cmd) => get_class($cmd), $allCommands);
        $this->assertContains(HelloCommand::class, $commandTypes);
        $this->assertContains(OptionalArgsCommand::class, $commandTypes);
    }
}
