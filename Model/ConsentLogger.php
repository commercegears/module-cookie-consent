<?php
/**
 * Copyright © Softaware Commerce. All rights reserved.
 */
declare(strict_types=1);

namespace Softaware\CookieConsent\Model;

use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Softaware\CookieConsent\Model\ResourceModel\Log as LogResource;

/**
 * Validates and stores one consent choice. No personal data is stored: the consent id is a random UUID
 * created in the browser and the IP address is only kept as a keyed (HMAC-SHA256) hash.
 */
class ConsentLogger
{
    public const ACTIONS = ['accept_all', 'reject_all', 'custom'];

    public function __construct(
        private readonly Config $config,
        private readonly LogResource $logResource,
        private readonly EncryptorInterface $encryptor,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * @param string[] $categories
     * @return bool true when a row was written
     * @throws LocalizedException
     */
    public function log(string $consentId, string $action, array $categories, int $version, string $ip): bool
    {
        $store = $this->storeManager->getStore();
        if (!$this->config->isEnabled() || !$this->config->isLogEnabled()) {
            return false;
        }
        if (!preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/', $consentId)) {
            throw new LocalizedException(__('Invalid consent id.'));
        }
        if (!in_array($action, self::ACTIONS, true)) {
            throw new LocalizedException(__('Invalid action.'));
        }
        $known = $this->config->getCategoryCodes();
        $categories = array_values(array_unique(array_intersect($categories, $known)));
        if (!in_array(Config::CATEGORY_NECESSARY, $categories, true)) {
            array_unshift($categories, Config::CATEGORY_NECESSARY);
        }
        $list = implode(',', $categories);
        $version = max(1, $version);
        if ($this->logResource->hasRecentDuplicate($consentId, $list, $version)) {
            return false;
        }
        $this->logResource->insertEntry([
            'consent_id' => $consentId,
            'store_id' => (int)$store->getId(),
            'action' => $action,
            'categories' => $list,
            'consent_version' => $version,
            'ip_hash' => $ip === '' ? null : $this->hashIp($ip),
        ]);
        return true;
    }

    public function hashIp(string $ip): string
    {
        if ($this->config->isLogAnonymiseIp()) {
            $ip = $this->truncate($ip);
        }
        return $this->encryptor->hash('sw_consent_ip|' . $ip);
    }

    private function truncate(string $ip): string
    {
        $packed = @inet_pton($ip);
        if ($packed === false) {
            return '';
        }
        if (strlen($packed) === 4) {
            return (string)inet_ntop(substr($packed, 0, 3) . "\0");
        }
        return (string)inet_ntop(substr($packed, 0, 6) . str_repeat("\0", 10));
    }
}
