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

namespace TestContainerApp;

use ConsoleApp\CommandProviderInterface;
use IteratorAggregate;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Traversable;

final class GreeterCommandProvider implements CommandProviderInterface, IteratorAggregate
{
    public function __construct(
        private readonly Greeter $greeter,
    ) {}

    public function getIterator(): Traversable
    {
        yield new class ($this->greeter) extends Command {
            public function __construct(private readonly Greeter $greeter)
            {
                parent::__construct('greet:provider');
            }

            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                $output->writeln($this->greeter->greet('provider'));

                return Command::SUCCESS;
            }
        };
    }
}
