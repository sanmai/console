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

use ConsoleApp\NewInstanceContainer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\ConsoleApp\Fixtures\HelloCommand;

#[CoversClass(NewInstanceContainer::class)]
final class NewInstanceContainerTest extends TestCase
{
    public function testGet(): void
    {
        $container = new NewInstanceContainer();

        $this->assertInstanceOf(HelloCommand::class, $container->get(HelloCommand::class));
    }

    public function testHas(): void
    {
        $container = new NewInstanceContainer();

        $this->assertTrue($container->has(HelloCommand::class));
        $this->assertFalse($container->has('NoSuchClass'));
    }
}
