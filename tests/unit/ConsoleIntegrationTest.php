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

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use Symfony\Component\Process\Process;

use function realpath;

#[CoversNothing]
class ConsoleIntegrationTest extends TestCase
{
    private static string $consolePath;

    public static function setUpBeforeClass(): void
    {
        self::$consolePath = realpath(__DIR__ . '/../bin/console');
    }

    public function testConsoleExecutesHelloCommand(): void
    {
        $process = $this->runCommand(['hello']);

        $this->assertTrue($process->isSuccessful());
        $this->assertStringContainsString('Hello, world!', $process->getOutput());
    }

    public function testConsoleListsCommands(): void
    {
        $process = $this->runCommand(['list']);

        $this->assertTrue($process->isSuccessful());
        $this->assertStringContainsString('hello', $process->getOutput());
        $this->assertStringContainsString('Available commands:', $process->getOutput());
    }

    public function testConsoleShowsHelpForHelloCommand(): void
    {
        $process = $this->runCommand(['help', 'hello']);

        $this->assertTrue($process->isSuccessful());
        $this->assertStringContainsString('hello', $process->getOutput());
    }

    public function testConsoleReturnsSuccessExitCode(): void
    {
        $process = $this->runCommand(['hello']);

        $this->assertEquals(0, $process->getExitCode());
    }

    public function testConsoleHandlesInvalidCommand(): void
    {
        $process = $this->runCommand(['non-existent-command']);

        $this->assertFalse($process->isSuccessful());
        $this->assertStringContainsString('Command "non-existent-command" is not defined.', $process->getErrorOutput());
    }

    /**
     * @param list<string> $command
     */
    private function runCommand(array $command): Process
    {
        $process = new Process(['php', self::$consolePath, ...$command]);
        $process->run();

        return $process;
    }
}
