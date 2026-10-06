<?php
/**
 * Copyright © Softaware Commerce. All rights reserved.
 */
declare(strict_types=1);

namespace Softaware\CookieConsent\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Finds scripts, iframes and images in Magento's design configuration (HTML Head and Footer) that load
 * without consent, per store view. Used for the admin warning; never runs on the storefront.
 */
class DesignScriptDetector
{
    public const PATH_HEAD = 'design/head/includes';
    public const PATH_FOOTER = 'design/footer/absolute_footer';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly StoreManagerInterface $storeManager,
        private readonly ScriptBlocker $blocker,
        private readonly Config $config
    ) {
    }

    /**
     * @return array<int, array{source: string, items: string[], stores: string[]}>
     *         one row per design field and identical findings, only for store views where they are not gated
     */
    public function detect(): array
    {
        $sources = [
            self::PATH_HEAD => (string)__('HTML Head > Scripts and Style Sheets'),
            self::PATH_FOOTER => (string)__('Footer > Miscellaneous HTML'),
        ];
        $rows = [];
        foreach ($this->storeManager->getStores() as $store) {
            $storeId = (int)$store->getId();
            if (!$store->getIsActive() || !$this->config->isEnabled($storeId)
                || $this->config->getDesignScriptsCategory($storeId) !== ''
            ) {
                continue;
            }
            foreach ($sources as $path => $label) {
                $html = (string)$this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
                $items = $this->blocker->findUngated($html);
                if ($items === []) {
                    continue;
                }
                $key = $path . '|' . implode('|', $items);
                $rows[$key] ??= ['source' => $label, 'items' => $items, 'stores' => []];
                $rows[$key]['stores'][] = (string)$store->getName();
            }
        }
        return array_values($rows);
    }
}
