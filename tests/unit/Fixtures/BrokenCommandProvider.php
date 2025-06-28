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

namespace Tests\ConsoleApp\Fixtures;

use ConsoleApp\CommandProviderInterface;
use IteratorAggregate;
use RuntimeException;
use Traversable;

class BrokenCommandProvider implements CommandProviderInterface, IteratorAggregate
{
    public function __construct()
    {
        throw new RuntimeException('Cannot instantiate');
    }

    public function getIterator(): Traversable
    {
        yield from [];
    }
}
