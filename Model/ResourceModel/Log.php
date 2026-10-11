<?php
/**
 * Copyright © CommerceGears. All rights reserved.
 */
declare(strict_types=1);

namespace CommerceGears\CookieConsent\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Log extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('commercegears_cookieconsent_log', 'log_id');
    }

    /**
     * @param array<string, mixed> $row
     */
    public function insertEntry(array $row): void
    {
        $this->getConnection()->insert($this->getMainTable(), $row);
    }

    /**
     * Was the same choice logged for this visitor in the last $seconds seconds (double clicks, retries)?
     */
    public function hasRecentDuplicate(string $consentId, string $categories, int $version, int $seconds = 10): bool
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable(), ['log_id'])
            ->where('consent_id = ?', $consentId)
            ->where('categories = ?', $categories)
            ->where('consent_version = ?', $version)
            ->where('created_at >= ?', gmdate('Y-m-d H:i:s', time() - $seconds))
            ->limit(1);
        return (bool)$connection->fetchOne($select);
    }

    /**
     * Number of entries written for this IP hash in the last $seconds seconds.
     */
    public function countRecentByIpHash(string $ipHash, int $seconds): int
    {
        $connection = $this->getConnection();
        return (int)$connection->fetchOne(
            $connection->select()
                ->from($this->getMainTable(), ['COUNT(*)'])
                ->where('ip_hash = ?', $ipHash)
                ->where('created_at >= ?', gmdate('Y-m-d H:i:s', time() - $seconds))
        );
    }

    /**
     * Deletes entries older than $days days in batches. Returns the number of deleted rows.
     */
    public function deleteOlderThan(int $days, int $batchSize = 5000): int
    {
        $connection = $this->getConnection();
        $cutoff = gmdate('Y-m-d H:i:s', time() - $days * 86400);
        $total = 0;
        do {
            $ids = $connection->fetchCol(
                $connection->select()
                    ->from($this->getMainTable(), ['log_id'])
                    ->where('created_at < ?', $cutoff)
                    ->limit($batchSize)
            );
            if ($ids) {
                $total += $connection->delete($this->getMainTable(), ['log_id IN (?)' => $ids]);
            }
        } while (count($ids) === $batchSize);
        return $total;
    }
}
