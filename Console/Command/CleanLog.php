<?php
/**
 * Copyright © Softaware Commerce. All rights reserved.
 */
declare(strict_types=1);

namespace Softaware\CookieConsent\Console\Command;

use Softaware\CookieConsent\Model\Config;
use Softaware\CookieConsent\Model\ResourceModel\Log as LogResource;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CleanLog extends Command
{
    public function __construct(
        private readonly Config $config,
        private readonly LogResource $logResource,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setName('softaware:cookie-consent:log:clean')
            ->setDescription('Delete consent log entries older than the retention period')
            ->addOption('days', null, InputOption::VALUE_REQUIRED, 'Override the configured retention (days)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $days = $input->getOption('days') !== null ? (int)$input->getOption('days') : $this->config->getLogRetentionDays();
        if ($days <= 0) {
            $output->writeln('<comment>Retention is 0 (keep forever); nothing deleted.</comment>');
            return Command::SUCCESS;
        }
        $deleted = $this->logResource->deleteOlderThan($days);
        $output->writeln(sprintf('<info>Deleted %d entries older than %d days.</info>', $deleted, $days));
        return Command::SUCCESS;
    }
}
