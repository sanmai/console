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
use ConsoleApp\CommandProviderHelper\Helper;
use IteratorAggregate;
use Symfony\Component\Console\Command\Command;
use Traversable;
use Override;

use function Pipeline\take;

/**
 * Command provider that discovers commands from Composer's classmap
 *
 * @implements IteratorAggregate<Command>
 */
final class ClassmapCommandProvider implements IteratorAggregate, CommandProviderInterface
{
    public function __construct(
        private readonly ClassLoader $classLoader,
        private readonly Helper $helper = new Helper(),
    ) {}

    #[Override]
    public function getIterator(): Traversable
    {
        return take($this->classLoader->getClassMap())
            ->stream()
            ->cast($this->helper->realpath(...))
            ->filter($this->helper->isNotVendoredDependency(...))
            ->filter($this->helper->hasCommandInFilename(...))
            ->keys()
            ->filter($this->helper->isCommandSubclass(...))
            ->cast($this->helper->newCommand(...))
            ->filter();
    }
}
