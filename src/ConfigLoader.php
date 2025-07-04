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

use Composer\InstalledVersions;
use Later\Interfaces\Deferred;
use SplFileObject;
use Composer\Autoload\ClassLoader;

use function json_decode;
use function Later\later;
use function spl_autoload_functions;
use function count;

use const JSON_THROW_ON_ERROR;

final class ConfigLoader
{
    /** @var array{install_path: string, ...} */
    private readonly array $rootPackage;

    /** @var Deferred<object> */
    private Deferred $config;

    /**
     * @param array{install_path: string}|null $rootPackage
     */
    public function __construct(
        readonly private ClassLoader $classLoader,
        ?array $rootPackage = null,
    ) {
        $this->rootPackage = $rootPackage ?? InstalledVersions::getRootPackage();
        $this->config = later($this->getConfig(...));
    }

    protected function readFile(string $path): string|false
    {
        $file = new SplFileObject($path, 'r');
        return $file->fread($file->getSize());
    }

    /**
     * @return iterable<object>
     */
    private function getConfig(): iterable
    {
        $path = $this->rootPackage['install_path'] . '/composer.json';

        yield (object) json_decode(
            json: (string) $this->readFile($path),
            associative: false,
            flags: JSON_THROW_ON_ERROR,
        );
    }

    /**
     * Returns the path to the bootstrap file.
     */
    public function getBootstrapPath(): string
    {
        // @phpstan-ignore-next-line
        return $this->config->get()->extra->console->bootstrap ?? '';
    }

    /**
     * Returns the class name of the command provider.
     *
     * @return array<class-string<CommandProviderInterface>>
     */
    public function getProviderClasses(): array
    {
        // @phpstan-ignore-next-line
        if (!isset($this->config->get()->extra->console->provider)) {
            return [];
        }

        // @phpstan-ignore return.type
        return (array) $this->config->get()->extra->console->provider;
    }

    public function handleAutoloader(callable $callback): void
    {
        $beforeCount = $this->getAutoloaderCount();

        $callback();

        if ($this->getAutoloaderCount() <= $beforeCount) {
            return;
        }

        // If the autoloader was registered, we need to unregister it
        $this->classLoader->unregister();
    }

    public function getAutoloaderCount(): int
    {
        return count(spl_autoload_functions());
    }
}
