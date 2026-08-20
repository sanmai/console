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

use Psr\Container\ContainerInterface;
use Override;

use function class_exists;

/**
 * Default container: creates a new instance of the class with no arguments.
 *
 * Not a PSR-11 compliant container: get() throws whatever `new $id()` throws.
 *
 * @internal
 */
final class NewInstanceContainer implements ContainerInterface
{
    /**
     * @param class-string $id
     */
    #[Override]
    public function get(string $id): object
    {
        return new $id();
    }

    #[Override]
    public function has(string $id): bool
    {
        return class_exists($id);
    }
}
