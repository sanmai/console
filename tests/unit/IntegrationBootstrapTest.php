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
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

use function chdir;
use function getcwd;
use function realpath;

#[CoversNothing]
final class IntegrationBootstrapTest extends TestCase
{
    private static string $originalDir;
    private static string $integrationDir;

    public static function setUpBeforeClass(): void
    {
        self::$originalDir = getcwd();
        self::$integrationDir = realpath(__DIR__ . '/../integration/bootstrap/');

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
        // Install dependencies using Composer
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

        // Verify vendor directory exists
        $this->assertDirectoryExists('vendor');
        $this->assertFileExists('vendor/autoload.php');
        $this->assertFileExists('vendor/bin/console');
    }

    #[Depends('testComposerInstallWorks')]
    public function testConsoleListCommand(): void
    {
        $process = $this->runConsoleCommand('list');

        $this->assertTrue($process->isSuccessful(), 'Console list command failed');
        $this->assertStringContainsString('Console App', $process->getOutput());
        $this->assertStringContainsString('Available commands:', $process->getOutput());

        $this->assertStringContainsString('test:bootstrap', $process->getOutput());
        $this->assertStringContainsString('Test command that proves the custom bootstrap was loaded', $process->getOutput());
    }

    #[Depends('testComposerInstallWorks')]
    public function testBootstrapCommandExecutesSuccessfully(): void
    {
        $process = $this->runConsoleCommand('test:bootstrap');

        $this->assertTrue($process->isSuccessful(), 'Bootstrap command failed');
        $this->assertStringContainsString('Custom bootstrap was loaded successfully', $process->getOutput());
    }

    #[Depends('testComposerInstallWorks')]
    #[TestDox('Test that the bootstrap script is loaded')]
    public function testBootstrapScriptIsLoaded(): void
    {
        // The test:bootstrap command already verifies that the bootstrap script was loaded
        // by checking for the BOOTSTRAP_LOADED constant. If the bootstrap wasn't loaded,
        // the command would fail and output an error message.
        $process = $this->runConsoleCommand('test:bootstrap');

        $this->assertTrue(
            $process->isSuccessful(),
            'Bootstrap was not loaded. Output: ' . $process->getOutput()
        );

        $this->assertStringContainsString(
            'Custom bootstrap was loaded successfully!',
            $process->getOutput()
        );
    }

    #[Depends('testComposerInstallWorks')]
    #[TestDox('Test that db:migrate and db:seed commands are available')]
    public function testDatabaseCommandsFromProvider(): void
    {
        $process = $this->runConsoleCommand('list');

        $this->assertTrue($process->isSuccessful());
        $output = $process->getOutput();
        $this->assertStringContainsString('db:migrate', $output, 'db:migrate command not found');
        $this->assertStringContainsString('db:seed', $output, 'db:seed command not found');
        $this->assertStringContainsString('Run database migrations', $output);
        $this->assertStringContainsString('Seed the database', $output);
    }

    #[Depends('testComposerInstallWorks')]
    public function testDatabaseMigrateCommand(): void
    {
        $process = $this->runConsoleCommand('db:migrate');

        $this->assertTrue($process->isSuccessful());
        $this->assertStringContainsString('Running migrations on localhost/test', $process->getOutput());
    }

    #[Depends('testComposerInstallWorks')]
    public function testDatabaseSeedCommand(): void
    {
        $process = $this->runConsoleCommand('db:seed');

        $this->assertTrue($process->isSuccessful());
        $this->assertStringContainsString('Seeding database test with test data', $process->getOutput());
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
