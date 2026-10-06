<?php
/**
 * Copyright © Softaware Commerce. All rights reserved.
 */
declare(strict_types=1);

namespace Softaware\CookieConsent\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class BarPosition implements OptionSourceInterface
{
    /**
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'bottom', 'label' => __('Bottom')],
            ['value' => 'top', 'label' => __('Top')],
        ];
    }
}
