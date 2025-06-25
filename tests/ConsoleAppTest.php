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

declare(strict_types=1);

namespace Tests\ConsoleApp;

use Composer\Autoload\ClassLoader;
use ConsoleApp\ClassmapCommandProvider;
use ConsoleApp\ConsoleApp;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\ApplicationTester;
use Tests\ConsoleApp\Fixtures\HelloCommand;

#[CoversClass(ConsoleApp::class)]
final class ConsoleAppTest extends TestCase
{
    private static ClassLoader $autoloader;

    public static function setUpBeforeClass(): void
    {
        self::$autoloader = require 'vendor/autoload.php';
    }

    public function testClassMapIncludesFixtures(): void
    {
        $this->assertArrayHasKey(
            HelloCommand::class,
            self::$autoloader->getClassMap(),
            'composer dump-autoload --optimize --dev'
        );
    }

    #[Depends('testClassMapIncludesFixtures')]
    public function testIt(): void
    {
        $app = new ConsoleApp(new ClassmapCommandProvider(self::$autoloader));

        $this->assertTrue(
            $app->has('hello'),
            'HelloCommand should be registered'
        );
    }

    #[Depends('testClassMapIncludesFixtures')]
    public function testListInterfacesCommandExecution(): void
    {
        $application = new ConsoleApp(new ClassmapCommandProvider(self::$autoloader));
        $application->setAutoExit(false);

        $tester = new ApplicationTester($application);
        $tester->run(['command' => 'hello']);

        $this->assertSame(0, $tester->getStatusCode());

        $this->assertStringContainsString('Hello, world!', $tester->getDisplay());
    }

    public function testVersion(): void
    {
        $app = new ConsoleApp($this->createMock(ClassmapCommandProvider::class));
        $version = $app->getVersion();

        $this->assertSame('UNKNOWN', $version);
    }
}
