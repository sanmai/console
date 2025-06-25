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

use Symfony\Component\Console\Application;
use VersionInfo\PlaceholderVersionReader;
use Override;

use function Pipeline\take;

final class ConsoleApp extends Application
{
    public const VERSION_INFO = '$Format:%h%d by %an +%ae$';

    public function __construct(private readonly ClassmapCommandProvider $commandProvider)
    {
        parent::__construct('Console App');
    }

    /** @codeCoverageIgnore */
    #[Override]
    public function getVersion(): string
    {
        $versionReader = new PlaceholderVersionReader(self::VERSION_INFO);
        return $versionReader->getVersionString() ?? parent::getVersion();
    }

    #[Override]
    protected function getDefaultCommands(): array
    {
        return take(parent::getDefaultCommands())
            ->append($this->commandProvider)
            ->toList();
    }
}
