<?php
/**
 * Copyright © Softaware Commerce. All rights reserved.
 */
declare(strict_types=1);

namespace Softaware\CookieConsent\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Store\Model\StoreManagerInterface;
use Softaware\CookieConsent\Model\Config;

/**
 * All cookie categories (built-in plus custom from any store view) for the admin script form.
 */
class Categories implements OptionSourceInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function toOptionArray(): array
    {
        $options = [
            Config::CATEGORY_NECESSARY => (string)__('Necessary (always runs)'),
            Config::CATEGORY_PREFERENCES => (string)__('Preferences'),
            Config::CATEGORY_ANALYTICS => (string)__('Analytics'),
            Config::CATEGORY_MARKETING => (string)__('Marketing'),
        ];
        foreach ($this->config->getCustomCategories(0) as $custom) {
            $options[$custom['code']] = $custom['label'] . ' (' . $custom['code'] . ')';
        }
        foreach ($this->storeManager->getStores() as $store) {
            foreach ($this->config->getCustomCategories((int)$store->getId()) as $custom) {
                $options[$custom['code']] ??= $custom['label'] . ' (' . $custom['code'] . ')';
            }
        }
        $result = [];
        foreach ($options as $value => $label) {
            $result[] = ['value' => (string)$value, 'label' => $label];
        }
        return $result;
    }
}
