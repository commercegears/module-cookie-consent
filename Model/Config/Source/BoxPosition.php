<?php
/**
 * Copyright © Softaware Commerce. All rights reserved.
 */
declare(strict_types=1);

namespace Softaware\CookieConsent\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class BoxPosition implements OptionSourceInterface
{
    /**
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'bottom-left', 'label' => __('Bottom left')],
            ['value' => 'bottom-right', 'label' => __('Bottom right')],
            ['value' => 'center', 'label' => __('Centre of the screen')],
        ];
    }
}
