<?php
/**
 * Copyright © CommerceGears. All rights reserved.
 */
declare(strict_types=1);

namespace CommerceGears\CookieConsent\Cron;

use Psr\Log\LoggerInterface;
use CommerceGears\CookieConsent\Model\Config;
use CommerceGears\CookieConsent\Model\ResourceModel\Log as LogResource;

/**
 * Deletes consent log entries older than the configured retention period.
 */
class CleanLog
{
    public function __construct(
        private readonly Config $config,
        private readonly LogResource $logResource,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(): void
    {
        $days = $this->config->getLogRetentionDays();
        if ($days <= 0) {
            return;
        }
        $deleted = $this->logResource->deleteOlderThan($days);
        if ($deleted > 0) {
            $this->logger->info(sprintf('CommerceGears_CookieConsent: deleted %d consent log entries older than %d days.', $deleted, $days));
        }
    }
}
