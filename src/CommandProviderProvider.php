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

use IteratorAggregate;
use Symfony\Component\Console\Command\Command;
use Traversable;
use Override;

use function Pipeline\take;

/**
 * @implements IteratorAggregate<Command>
 */
final class CommandProviderProvider implements IteratorAggregate, CommandProviderInterface
{
    /** @var array<CommandProviderInterface> */
    private readonly array $providers;

    public function __construct(
        CommandProviderInterface ...$providers,
    ) {
        $this->providers = $providers;
    }

    #[Override]
    public function getIterator(): Traversable
    {
        return take(...$this->providers);
    }
}
