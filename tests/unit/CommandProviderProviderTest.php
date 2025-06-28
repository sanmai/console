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

use ConsoleApp\CommandProviderProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\ConsoleApp\Fixtures\TestCommandProvider;
use Tests\ConsoleApp\Fixtures\HelloCommand;
use Tests\ConsoleApp\Fixtures\OptionalArgsCommand;

use function iterator_to_array;

#[CoversClass(CommandProviderProvider::class)]
final class CommandProviderProviderTest extends TestCase
{
    public function testItProvides(): void
    {
        $provider = new CommandProviderProvider(new TestCommandProvider(), new TestCommandProvider());

        $commands = iterator_to_array($provider, false);

        $this->assertCount(4, $commands);
        $this->assertInstanceOf(HelloCommand::class, $commands[0]);
        $this->assertInstanceOf(OptionalArgsCommand::class, $commands[1]);
        $this->assertInstanceOf(HelloCommand::class, $commands[2]);
    }
}
