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

namespace ConsoleApp;

use Composer\Autoload\ClassLoader;
use Composer\InstalledVersions;
use RuntimeException;

use function class_exists;
use function file_exists;
use function file_get_contents;
use function fwrite;
use function getcwd;
use function is_readable;
use function is_array;
use function json_decode;

use const STDERR;

final class ConfigLoader
{
    public function __construct(
        private readonly string $workingDirectory = ''
    ) {}

    /**
     * Loads configuration from the root package composer.json
     *
     * @return array<string, mixed>
     */
    public function loadConfig(): array
    {
        if (!class_exists(InstalledVersions::class)) {
            return [];
        }

        // Get the install path for the root package
        $rootPackage = InstalledVersions::getRootPackage();
        $installPath = $rootPackage['install_path'];

        $composerJsonPath = $installPath . '/composer.json';

        if (!file_exists($composerJsonPath) || !is_readable($composerJsonPath)) {
            return [];
        }

        $content = file_get_contents($composerJsonPath);
        if (false === $content) {
            return [];
        }

        $composer = json_decode($content, true);
        if (!is_array($composer)) {
            return [];
        }

        if (!isset($composer['extra']) || !is_array($composer['extra'])) {
            return [];
        }

        if (!isset($composer['extra']['console']) || !is_array($composer['extra']['console'])) {
            return [];
        }

        /** @var array<string, mixed> */
        return $composer['extra']['console'];
    }

    /**
     * Loads a custom bootstrap script
     *
     * Similar to PHPUnit, bootstrap scripts are included for their side effects.
     * They can optionally return a ClassLoader instance to use for command discovery.
     */
    public function loadCustomInit(
        ClassLoader $initialLoader,
        string $initScript
    ): ?ClassLoader {
        $initPath = $this->getWorkingDirectory() . '/' . $initScript;

        if (!file_exists($initPath)) {
            $this->writeWarning("Bootstrap script not found: $initScript");
            return null;
        }

        if (!is_readable($initPath)) {
            $this->writeWarning("Bootstrap script not readable: $initScript");
            return null;
        }

        // Load the custom bootstrap script (like PHPUnit does)
        $result = require $initPath;

        // If bootstrap returns a ClassLoader, use it
        // Otherwise, return null to indicate the initial loader should be used
        if ($result instanceof ClassLoader) {
            return $result;
        }

        return null;
    }

    private function getWorkingDirectory(): string
    {
        if ($this->workingDirectory) {
            return $this->workingDirectory;
        }

        $cwd = getcwd();
        if (false === $cwd) {
            throw new RuntimeException('Unable to determine current working directory');
        }

        return $cwd;
    }

    private function writeWarning(string $message): void
    {
        fwrite(STDERR, "Warning: $message\n");
    }
}
