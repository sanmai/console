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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ConsoleApp\ConfigLoader;
use Composer\Autoload\ClassLoader;
use RuntimeException;
use ReflectionClass;

use function array_diff;
use function chdir;
use function chmod;
use function class_exists;
use function file_put_contents;
use function getcwd;
use function is_dir;
use function mkdir;
use function rmdir;
use function scandir;
use function strlen;
use function sys_get_temp_dir;
use function touch;
use function uniqid;
use function unlink;

/**
 * Comprehensive tests for ConfigLoader to achieve high mutation score
 */
#[CoversClass(ConfigLoader::class)]
class ConfigLoaderComprehensiveTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/config-loader-test-' . uniqid();
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    /**
     * Test loadConfig when composer.json path is null (no InstalledVersions)
     */
    public function testLoadConfigWhenComposerJsonPathIsNull(): void
    {
        $loader = $this->getMockBuilder(ConfigLoader::class)
            ->onlyMethods(['getComposerJsonPath'])
            ->getMock();

        $loader->expects($this->once())
            ->method('getComposerJsonPath')
            ->willReturn(null);

        $this->assertSame([], $loader->loadConfig());
    }

    /**
     * Test loadConfig when file content is null (file not readable)
     */
    public function testLoadConfigWhenFileContentIsNull(): void
    {
        $loader = $this->getMockBuilder(ConfigLoader::class)
            ->onlyMethods(['getComposerJsonPath', 'readFile'])
            ->getMock();

        $loader->expects($this->once())
            ->method('getComposerJsonPath')
            ->willReturn('/path/to/composer.json');

        $loader->expects($this->once())
            ->method('readFile')
            ->with('/path/to/composer.json')
            ->willReturn(null);

        $this->assertSame([], $loader->loadConfig());
    }

    /**
     * Test loadConfig with various JSON content
     */
    public function testLoadConfigWithVariousJsonContent(): void
    {
        $testCases = [
            'invalid json' => ['invalid', []],
            'null' => ['null', []],
            'empty object' => ['{}', []],
            'valid config' => ['{"extra":{"console":{"key":"value"}}}', ['key' => 'value']],
        ];

        foreach ($testCases as $name => $case) {
            [$json, $expected] = $case;

            $loader = $this->getMockBuilder(ConfigLoader::class)
                ->onlyMethods(['getComposerJsonPath', 'readFile'])
                ->getMock();

            $loader->expects($this->once())
                ->method('getComposerJsonPath')
                ->willReturn('/path/to/composer.json');

            $loader->expects($this->once())
                ->method('readFile')
                ->willReturn($json);

            $this->assertSame($expected, $loader->loadConfig(), "Failed for case: $name");
        }
    }

    /**
     * Test getComposerJsonPath concatenation
     */
    public function testGetComposerJsonPathConcatenation(): void
    {
        $loader = new class extends ConfigLoader {
            public function testGetPath(): ?string
            {
                return $this->getComposerJsonPath();
            }
        };

        $path = $loader->testGetPath();

        if (class_exists(\Composer\InstalledVersions::class)) {
            $this->assertNotNull($path);
            // Ensure path is correctly concatenated
            $this->assertStringEndsWith('/composer.json', $path);
            $this->assertGreaterThan(strlen('/composer.json'), strlen($path));
            // Ensure it's not just '/composer.json'
            $this->assertNotSame('/composer.json', $path);
        }
    }

    /**
     * Test readFile with all conditions
     */
    public function testReadFileConditions(): void
    {
        $loader = new class extends ConfigLoader {
            public function testRead(string $path): ?string
            {
                return $this->readFile($path);
            }
        };

        // Non-existent file
        $this->assertNull($loader->testRead('/non/existent/file'));

        // Existing readable file
        $testFile = $this->tempDir . '/test.txt';
        file_put_contents($testFile, 'content');
        $this->assertSame('content', $loader->testRead($testFile));

        // Unreadable file
        chmod($testFile, 0000);
        $this->assertNull($loader->testRead($testFile));
        chmod($testFile, 0644); // restore for cleanup
    }

    /**
     * Test extractConsoleConfig with all conditions
     */
    public function testExtractConsoleConfig(): void
    {
        $loader = new class extends ConfigLoader {
            public function testExtract(array $data): array
            {
                return $this->extractConsoleConfig($data);
            }
        };

        // Test all branches
        $this->assertSame([], $loader->testExtract([]));
        $this->assertSame([], $loader->testExtract(['extra' => null]));
        $this->assertSame([], $loader->testExtract(['extra' => 'string']));
        $this->assertSame([], $loader->testExtract(['extra' => []]));
        $this->assertSame([], $loader->testExtract(['extra' => ['console' => null]]));
        $this->assertSame([], $loader->testExtract(['extra' => ['console' => 'string']]));
        $this->assertSame(
            ['data' => 'value'],
            $loader->testExtract(['extra' => ['console' => ['data' => 'value']]])
        );
    }

    /**
     * Test working directory behavior
     */
    public function testWorkingDirectory(): void
    {
        // Test with provided directory
        $loader = new ConfigLoader('/custom/dir');

        $reflection = new ReflectionClass($loader);
        $method = $reflection->getMethod('getWorkingDirectory');
        $method->setAccessible(true);

        $this->assertSame('/custom/dir', $method->invoke($loader));

        // Test with getcwd
        $loader2 = new ConfigLoader();
        $this->assertSame(getcwd(), $method->invoke($loader2));
    }

    /**
     * Test loadCustomInit exceptions
     */
    public function testLoadCustomInitExceptions(): void
    {
        $loader = new ConfigLoader($this->tempDir);
        $classLoader = $this->createMock(ClassLoader::class);

        // Non-existent file
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Bootstrap script not found: missing.php');
        $loader->loadCustomInit($classLoader, 'missing.php');
    }

    public function testLoadCustomInitUnreadable(): void
    {
        $loader = new ConfigLoader($this->tempDir);
        $classLoader = $this->createMock(ClassLoader::class);

        // Create unreadable file
        $file = $this->tempDir . '/unreadable.php';
        touch($file);
        chmod($file, 0000);

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Bootstrap script not readable: unreadable.php');
            $loader->loadCustomInit($classLoader, 'unreadable.php');
        } finally {
            chmod($file, 0644);
        }
    }

    /**
     * Test loadCustomInit success cases
     */
    public function testLoadCustomInitSuccess(): void
    {
        $loader = new ConfigLoader($this->tempDir);
        $classLoader = $this->createMock(ClassLoader::class);

        // Script returns null
        $file1 = $this->tempDir . '/null.php';
        file_put_contents($file1, '<?php return null;');
        $this->assertNull($loader->loadCustomInit($classLoader, 'null.php'));

        // Script returns ClassLoader
        $file2 = $this->tempDir . '/loader.php';
        file_put_contents($file2, '<?php return $GLOBALS["test_loader"];');
        $GLOBALS['test_loader'] = $classLoader;
        $this->assertSame($classLoader, $loader->loadCustomInit($classLoader, 'loader.php'));
        unset($GLOBALS['test_loader']);
    }

    /**
     * Test getcwd failure
     */
    public function testGetcwdFailure(): void
    {
        // Create a directory we can remove
        $testDir = $this->tempDir . '/removeme';
        mkdir($testDir);
        $oldCwd = getcwd();
        chdir($testDir);
        rmdir($testDir);

        $loader = new ConfigLoader();

        try {
            $reflection = new ReflectionClass($loader);
            $method = $reflection->getMethod('getWorkingDirectory');
            $method->setAccessible(true);
            $method->invoke($loader);
            $this->fail('Expected exception not thrown');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Unable to determine current working directory', $e->getMessage());
        } finally {
            chdir($oldCwd);
        }
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
                @chmod($path, 0644);
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
