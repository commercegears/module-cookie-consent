<?php
/**
 * Copyright © Softaware Commerce. All rights reserved.
 */
declare(strict_types=1);

namespace Softaware\CookieConsent\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ReopenLink implements OptionSourceInterface
{
    /**
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'none', 'label' => __('None (widget or #cookie-settings links only)')],
            ['value' => 'footer', 'label' => __('Footer link')],
            ['value' => 'floating', 'label' => __('Floating button')],
            ['value' => 'footer_floating', 'label' => __('Footer link and floating button')],
        ];
    }
}
