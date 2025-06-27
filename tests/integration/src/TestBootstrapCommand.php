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

namespace TestApp;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function defined;

#[AsCommand(
    name: 'test:bootstrap',
    description: 'Test command that proves the custom bootstrap was loaded',
)]
final class TestBootstrapCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (defined('BOOTSTRAP_LOADED') && BOOTSTRAP_LOADED) {
            $output->writeln('✓ Custom bootstrap was loaded successfully!');
            $output->writeln('Bootstrap time: ' . (defined('BOOTSTRAP_TIME') ? BOOTSTRAP_TIME : 'unknown'));
            return Command::SUCCESS;
        }

        $output->writeln('✗ Custom bootstrap was NOT loaded');
        return Command::FAILURE;
    }
}
