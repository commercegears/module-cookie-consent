<?php
/**
 * Copyright © CommerceGears. All rights reserved.
 */
declare(strict_types=1);

namespace CommerceGears\CookieConsent\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class LogAction implements OptionSourceInterface
{
    /**
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'accept_all', 'label' => __('Accepted all')],
            ['value' => 'reject_all', 'label' => __('Rejected all')],
            ['value' => 'custom', 'label' => __('Custom choice')],
        ];
    }
}
