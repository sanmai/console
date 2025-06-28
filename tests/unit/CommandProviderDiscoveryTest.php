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
use ConsoleApp\CommandProviderHelper\Helper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ConsoleApp\CommandProviderDiscovery;
use ReflectionClass;
use Symfony\Component\Console\Command\Command;
use Tests\ConsoleApp\Fixtures\TestCommandProvider;
use Tests\ConsoleApp\Fixtures\HelloCommand;
use Tests\ConsoleApp\Fixtures\OptionalArgsCommand;

use function iterator_to_array;

#[CoversClass(CommandProviderDiscovery::class)]
class CommandProviderDiscoveryTest extends TestCase
{
    public function testItUsesHelper(): void
    {
        $fullPath = (new ReflectionClass(HelloCommand::class))->getFileName();

        $loader = $this->createMock(ClassLoader::class);

        $loader->expects($this->once())
            ->method('getClassMap')
            ->willReturn([TestCommandProvider::class => '../../src/TestCommandProvider.php']);

        $helper = $this->createMock(Helper::class);

        $helper->expects($this->once())
            ->method('realpath')
            ->with('../../src/TestCommandProvider.php')
            ->willReturn($fullPath);

        $helper->expects($this->once())
            ->method('isNotVendoredDependency')
            ->with($fullPath)
            ->willReturn(true);

        $helper->expects($this->once())
            ->method('isNotOurNamespace')
            ->with(TestCommandProvider::class)
            ->willReturn(true);

        $helper->expects($this->once())
            ->method('isCommandProviderSubclass')
            ->with(TestCommandProvider::class)
            ->willReturn(true);

        $mockCommand = $this->createMock(Command::class);

        $helper->expects($this->once())
            ->method('newCommandProvider')
            ->with(TestCommandProvider::class)
            ->willReturn(new TestCommandProvider($mockCommand));

        $provider = new CommandProviderDiscovery($loader, $helper);

        $this->assertSame([$mockCommand], iterator_to_array($provider, false));
    }
}
