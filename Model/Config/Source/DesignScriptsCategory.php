<?php
/**
 * Copyright © Softaware Commerce. All rights reserved.
 */
declare(strict_types=1);

namespace Softaware\CookieConsent\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Softaware\CookieConsent\Model\Config;

/**
 * "Scripts in Design Configuration": leave unchanged, or the category whose consent they wait for.
 */
class DesignScriptsCategory implements OptionSourceInterface
{
    public function __construct(private readonly Categories $categories)
    {
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function toOptionArray(): array
    {
        $options = [['value' => '', 'label' => (string)__('Run without consent (unchanged)')]];
        foreach ($this->categories->toOptionArray() as $option) {
            if ($option['value'] !== Config::CATEGORY_NECESSARY) {
                $options[] = ['value' => $option['value'], 'label' => (string)__('Wait for consent: %1', $option['label'])];
            }
        }
        return $options;
    }
}
