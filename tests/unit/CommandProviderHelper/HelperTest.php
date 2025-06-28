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

namespace Tests\ConsoleApp\CommandProviderHelper;

use ConsoleApp\CommandProviderDiscovery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ConsoleApp\CommandProviderHelper\Helper;
use Tests\ConsoleApp\Fixtures\AbstractCommand;
use Tests\ConsoleApp\Fixtures\HelloCommand;
use Tests\ConsoleApp\Fixtures\TestCommandProvider;

use function iterator_to_array;
use function realpath;

#[CoversClass(Helper::class)]
class HelperTest extends TestCase
{
    private Helper $helper;

    public function setUp(): void
    {
        $this->helper = new Helper();
    }

    public function testItCallsRealpath(): void
    {
        $this->assertSame(
            realpath(__FILE__),
            $this->helper->realpath(__FILE__),
        );

        $this->assertSame(
            'invalid/path/to/file.php',
            $this->helper->realpath('invalid/path/to/file.php'),
        );
    }

    public function testItChecksVendoredDependency(): void
    {
        $this->assertTrue($this->helper->isNotVendoredDependency('/tmp/src/Example.php'));
        $this->assertFalse($this->helper->isNotVendoredDependency('/tmp/vendor/some/package/src/Example.php'));
    }

    public function testItChecksCommandSuffix(): void
    {
        $this->assertTrue($this->helper->hasCommandSuffix(HelloCommand::class));
        $this->assertFalse($this->helper->hasCommandSuffix(Helper::class));
    }

    public function testItChecksCommandSubclass(): void
    {
        $this->assertTrue($this->helper->isCommandSubclass(HelloCommand::class));
        $this->assertFalse($this->helper->isCommandSubclass(Helper::class));
    }

    public function testNewCommand(): void
    {
        $this->assertInstanceOf(
            HelloCommand::class,
            $this->helper->newCommand(HelloCommand::class),
        );

        $this->assertNull($this->helper->newCommand(AbstractCommand::class));
    }

    public function testItChecksOurNamespace(): void
    {
        $this->assertTrue($this->helper->isNotOurNamespace(TestCommandProvider::class));
        $this->assertTrue($this->helper->isNotOurNamespace('ConsoleApp'));
        $this->assertFalse($this->helper->isNotOurNamespace(CommandProviderDiscovery::class));
    }

    public function testItChecksCommandProviderSubclass(): void
    {
        $this->assertTrue($this->helper->isCommandProviderSubclass(TestCommandProvider::class));
        $this->assertFalse($this->helper->isCommandProviderSubclass(HelloCommand::class));
        $this->assertFalse($this->helper->isCommandProviderSubclass(Helper::class));
    }

    public function testItChecksCommandProviderSuffix(): void
    {
        $this->assertTrue($this->helper->hasCommandProviderSuffix(TestCommandProvider::class));
        $this->assertFalse($this->helper->hasCommandProviderSuffix(HelloCommand::class));
    }

    public function testNewCommandProvider(): void
    {
        $provider = $this->helper->newCommandProvider(TestCommandProvider::class);
        $this->assertInstanceOf(TestCommandProvider::class, $provider);

        $commands = iterator_to_array($provider);
        $this->assertCount(2, $commands);
        $this->assertInstanceOf(HelloCommand::class, $commands[0]);
    }

}
