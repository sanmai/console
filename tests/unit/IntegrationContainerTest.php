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

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

use function chdir;
use function getcwd;
use function realpath;

#[CoversNothing]
final class IntegrationContainerTest extends TestCase
{
    private static string $originalDir;
    private static string $integrationDir;

    public static function setUpBeforeClass(): void
    {
        self::$originalDir = getcwd();
        self::$integrationDir = realpath(__DIR__ . '/../integration/container/');

        chdir(self::$integrationDir);
    }

    public static function tearDownAfterClass(): void
    {
        chdir(self::$originalDir);
    }

    public function testIntegrationProjectIsSetUp(): void
    {
        $this->assertDirectoryExists(self::$integrationDir);

        $this->assertFileExists('composer.json');
        $this->assertFileExists('src/GreetCommand.php');
        $this->assertFileExists('src/GreeterCommandProvider.php');
    }

    public function testComposerInstallWorks(): void
    {
        $process = new Process(['composer', 'install', '--no-interaction']);
        $process->setWorkingDirectory(self::$integrationDir);
        $process->run();

        $this->assertTrue(
            $process->isSuccessful(),
            'Composer install failed: ' . $process->getErrorOutput()
        );

        // Always regenerate autoload to ensure commands are discovered
        $process = new Process(['composer', 'dump-autoload', '-o']);
        $process->setWorkingDirectory(self::$integrationDir);
        $process->run();

        $this->assertTrue(
            $process->isSuccessful(),
            'Composer dump-autoload failed: ' . $process->getErrorOutput()
        );

        $this->assertFileExists('vendor/autoload.php');
        $this->assertFileExists('vendor/bin/console');
    }

    #[Depends('testComposerInstallWorks')]
    public function testConsoleListCommand(): void
    {
        $process = $this->runConsoleCommand('list');

        $this->assertTrue($process->isSuccessful(), 'Console list command failed');
        $this->assertStringContainsString('Available commands:', $process->getOutput());

        $this->assertStringContainsString('greet', $process->getOutput());
        $this->assertStringContainsString('A command with a constructor dependency', $process->getOutput());
        $this->assertStringContainsString('greet:provider', $process->getOutput());
    }

    #[Depends('testComposerInstallWorks')]
    public function testContainerBuildsCommand(): void
    {
        $process = $this->runConsoleCommand('greet');

        $this->assertTrue($process->isSuccessful(), 'Command failed: ' . $process->getErrorOutput());
        $this->assertStringContainsString('Hello, container!', $process->getOutput());
    }

    #[Depends('testComposerInstallWorks')]
    public function testContainerBuildsCommandProvider(): void
    {
        $process = $this->runConsoleCommand('greet:provider');

        $this->assertTrue($process->isSuccessful(), 'Command failed: ' . $process->getErrorOutput());
        $this->assertStringContainsString('Hello, provider!', $process->getOutput());
    }

    /**
     * @param string ...$command
     */
    private function runConsoleCommand(string ...$command): Process
    {
        $process = new Process(['php', 'vendor/bin/console', ...$command]);
        $process->setWorkingDirectory(self::$integrationDir);
        $process->run();

        return $process;
    }
}
