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
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

use function chdir;
use function getcwd;
use function file_exists;

#[CoversNothing]
class IntegrationBootstrapTest extends TestCase
{
    private static string $originalDir;
    private static string $integrationDir;

    public static function setUpBeforeClass(): void
    {
        self::$originalDir = getcwd();
        self::$integrationDir = realpath(__DIR__ . '/../integration');
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
        $this->assertFileExists('bootstrap.php');
        $this->assertFileExists('src/TestBootstrapCommand.php');
    }

    public function testComposerInstallWorks(): void
    {
        // Run composer install if vendor doesn't exist
        if (!file_exists('vendor')) {
            $process = new Process(['composer', 'install', '--no-interaction']);
            $process->setWorkingDirectory(self::$integrationDir);
            $process->run();

            $this->assertTrue(
                $process->isSuccessful(),
                'Composer install failed: ' . $process->getErrorOutput()
            );
        }

        // Always regenerate autoload to ensure commands are discovered
        $process = new Process(['composer', 'dump-autoload', '-o']);
        $process->setWorkingDirectory(self::$integrationDir);
        $process->run();

        $this->assertTrue(
            $process->isSuccessful(),
            'Composer dump-autoload failed: ' . $process->getErrorOutput()
        );

        // Verify vendor directory exists
        $this->assertDirectoryExists('vendor');
        $this->assertFileExists('vendor/autoload.php');
        $this->assertFileExists('vendor/bin/console');
    }

    public function testConsoleListCommand(): void
    {
        // Ensure composer dependencies are installed
        $this->testComposerInstallWorks();

        // Run console list command
        $process = $this->runConsoleCommand(['list']);

        $this->assertTrue($process->isSuccessful(), 'Console list command failed');
        $this->assertStringContainsString('Console App', $process->getOutput());
        $this->assertStringContainsString('Available commands:', $process->getOutput());
    }

    public function testBootstrapCommandAppearsInList(): void
    {
        // Ensure composer dependencies are installed
        $this->testComposerInstallWorks();

        // Run console list command
        $process = $this->runConsoleCommand(['list']);

        $this->assertTrue($process->isSuccessful());
        $this->assertStringContainsString('test:bootstrap', $process->getOutput());
        $this->assertStringContainsString('Test command that proves the custom bootstrap was loaded', $process->getOutput());
    }

    public function testBootstrapCommandExecutesSuccessfully(): void
    {
        // Ensure composer dependencies are installed
        $this->testComposerInstallWorks();

        // Run the test:bootstrap command
        $process = $this->runConsoleCommand(['test:bootstrap']);

        $this->assertTrue($process->isSuccessful(), 'Bootstrap command failed');
        $this->assertStringContainsString('Custom bootstrap was loaded successfully', $process->getOutput());
        $this->assertStringContainsString('Bootstrap time:', $process->getOutput());
    }

    public function testBootstrapScriptIsLoaded(): void
    {
        // Ensure composer dependencies are installed
        $this->testComposerInstallWorks();

        // The test:bootstrap command already verifies that the bootstrap script was loaded
        // by checking for the BOOTSTRAP_LOADED constant. If the bootstrap wasn't loaded,
        // the command would fail and output an error message.
        $process = $this->runConsoleCommand(['test:bootstrap']);

        $this->assertTrue(
            $process->isSuccessful(),
            'Bootstrap was not loaded. Output: ' . $process->getOutput()
        );

        // Verify the command confirms bootstrap was loaded
        $this->assertStringContainsString(
            '✓ Custom bootstrap was loaded successfully!',
            $process->getOutput()
        );
    }

    /**
     * @param list<string> $command
     */
    private function runConsoleCommand(array $command): Process
    {
        $process = new Process(['php', 'vendor/bin/console', ...$command]);
        $process->setWorkingDirectory(self::$integrationDir);
        $process->run();

        return $process;
    }
}
