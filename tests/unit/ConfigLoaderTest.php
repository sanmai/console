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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ConsoleApp\ConfigLoader;
use Composer\Autoload\ClassLoader;
use JsonException;

#[CoversClass(ConfigLoader::class)]
final class ConfigLoaderTest extends TestCase
{
    public function testGetBootstrapPath(): void
    {
        $classLoader = $this->createMock(ClassLoader::class);

        $configLoader = new ConfigLoader($classLoader, [
            'install_path' => __DIR__ . '/../integration',
        ]);

        $this->assertSame(
            'bootstrap.php',
            $configLoader->getBootstrapPath()
        );
    }

    public function testGetBootstrapPathNone(): void
    {
        $classLoader = $this->createMock(ClassLoader::class);

        $configLoader = new ConfigLoader($classLoader);

        $this->assertSame(
            '',
            $configLoader->getBootstrapPath()
        );
    }

    public function testGetBootstrapPathFileReadError(): void
    {
        $classLoader = $this->createMock(ClassLoader::class);

        $configLoader = $this->getMockBuilder(ConfigLoader::class)
            ->setConstructorArgs([$classLoader, ['install_path' => '']])
            ->onlyMethods(['readFile'])
            ->getMock();

        $configLoader->expects($this->once())
            ->method('readFile')
            ->willReturn(false);

        $this->expectException(JsonException::class);
        $configLoader->getBootstrapPath();
    }

    public function testAutoloaderCount(): void
    {
        $classLoader = $this->createMock(ClassLoader::class);
        $configLoader = new ConfigLoader($classLoader, ['install_path' => '']);

        $this->assertGreaterThan(0, $configLoader->getAutoloaderCount());
    }

    public function testHandleAutoloaderNotUnloads(): void
    {
        $classLoader = $this->createMock(ClassLoader::class);

        $classLoader->expects($this->never())
            ->method('unregister');

        $configLoader = $this->getMockBuilder(ConfigLoader::class)
            ->setConstructorArgs([$classLoader, ['install_path' => '']])
            ->onlyMethods(['getAutoloaderCount'])
            ->getMock();

        $configLoader->method('getAutoloaderCount')
            ->willReturn(1, 1);

        $wasCalled = false;
        $spy = function () use (&$wasCalled) {
            $wasCalled = true;
        };

        $configLoader->handleAutoloader($spy);

        $this->assertTrue($wasCalled, 'Spy function was not called');
    }

    public function testHandleAutoloaderUnloads(): void
    {
        $classLoader = $this->createMock(ClassLoader::class);

        $classLoader->expects($this->once())
            ->method('unregister');

        $configLoader = $this->getMockBuilder(ConfigLoader::class)
            ->setConstructorArgs([$classLoader, ['install_path' => '']])
            ->onlyMethods(['getAutoloaderCount'])
            ->getMock();

        $configLoader->expects($this->exactly(2))
            ->method('getAutoloaderCount')
            ->willReturn(1, 2);

        $configLoader->handleAutoloader(fn() => true);
    }
}
