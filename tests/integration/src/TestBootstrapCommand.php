<?php

/**
 * Test command that proves the custom bootstrap was loaded
 */

declare(strict_types=1);

namespace TestApp;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'test:bootstrap',
    description: 'Tests that custom bootstrap was loaded',
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