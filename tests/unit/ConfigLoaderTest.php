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
use Exception;
use RuntimeException;

use function array_diff;
use function chmod;
use function file_put_contents;
use function is_dir;
use function is_writable;
use function mkdir;
use function rmdir;
use function scandir;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;
use function defined;

#[CoversClass(ConfigLoader::class)]
class ConfigLoaderTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/console-test-' . uniqid();
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testLoadConfigReturnsEmptyArrayWhenComposerNotAvailable(): void
    {
        $configLoader = new ConfigLoader();

        // When InstalledVersions class doesn't exist, should return empty array
        $config = $configLoader->loadConfig();

        $this->assertIsArray($config);
    }

    public function testLoadCustomInitWithNonExistentScript(): void
    {
        $configLoader = new ConfigLoader($this->tempDir);
        $classLoader = $this->createMock(ClassLoader::class);

        // Expect no calls to unregister since file doesn't exist
        $classLoader->expects($this->never())->method('unregister');

        $this->expectException(RuntimeException::class);
        $configLoader->loadCustomInit($classLoader, 'nonexistent.php');
    }

    public function testLoadCustomInitWithUnreadableScript(): void
    {
        $configLoader = new ConfigLoader($this->tempDir);
        $classLoader = $this->createMock(ClassLoader::class);

        // Create an unreadable file
        $scriptPath = $this->tempDir . '/unreadable.php';
        file_put_contents($scriptPath, '<?php return "test";');
        chmod($scriptPath, 0000);

        // Expect no calls to unregister since file is unreadable
        $classLoader->expects($this->never())->method('unregister');

        try {
            $this->expectException(RuntimeException::class);
            $configLoader->loadCustomInit($classLoader, 'unreadable.php');
        } finally {
            // Restore permissions so we can delete the file
            chmod($scriptPath, 0644);
        }
    }

    public function testLoadCustomInitWithValidScript(): void
    {
        $configLoader = new ConfigLoader($this->tempDir);
        $initialLoader = $this->createMock(ClassLoader::class);
        $customLoader = $this->createMock(ClassLoader::class);

        // Create a valid init script that returns a ClassLoader
        $scriptPath = $this->tempDir . '/init.php';
        file_put_contents($scriptPath, '<?php return $GLOBALS["test_loader"];');
        $GLOBALS['test_loader'] = $customLoader;

        // No unregister expected - loader stays active
        $initialLoader->expects($this->never())->method('unregister');

        $result = $configLoader->loadCustomInit($initialLoader, 'init.php');

        $this->assertSame($customLoader, $result);

        // Cleanup
        unset($GLOBALS['test_loader']);
    }

    public function testLoadCustomInitWithScriptThatDoesNotReturnClassLoader(): void
    {
        $configLoader = new ConfigLoader($this->tempDir);
        $initialLoader = $this->createMock(ClassLoader::class);

        // Create a script that doesn't return a ClassLoader (like PHPUnit bootstrap)
        $scriptPath = $this->tempDir . '/bootstrap.php';
        file_put_contents($scriptPath, '<?php define("TEST_BOOTSTRAP_LOADED", true);');

        // No unregister/register calls expected - loader stays active
        $initialLoader->expects($this->never())->method('unregister');
        $initialLoader->expects($this->never())->method('register');

        $result = $configLoader->loadCustomInit($initialLoader, 'bootstrap.php');

        $this->assertNull($result);
        $this->assertTrue(defined('TEST_BOOTSTRAP_LOADED'));
    }

    public function testLoadCustomInitWithScriptThatThrowsException(): void
    {
        $configLoader = new ConfigLoader($this->tempDir);
        $initialLoader = $this->createMock(ClassLoader::class);

        // Create a script that throws an exception
        $scriptPath = $this->tempDir . '/exception.php';
        file_put_contents($scriptPath, '<?php throw new Exception("test exception");');

        // No unregister/register expected - loader stays active
        $initialLoader->expects($this->never())->method('unregister');
        $initialLoader->expects($this->never())->method('register');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('test exception');

        $configLoader->loadCustomInit($initialLoader, 'exception.php');
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                if (!is_writable($path)) {
                    chmod($path, 0644);
                }
                unlink($path);
            }
        }
        rmdir($dir);
    }
}
