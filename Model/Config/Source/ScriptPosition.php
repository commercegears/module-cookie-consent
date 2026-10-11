<?php
/**
 * Copyright © CommerceGears. All rights reserved.
 */
declare(strict_types=1);

namespace CommerceGears\CookieConsent\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ScriptPosition implements OptionSourceInterface
{
    /**
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'head', 'label' => __('Page head')],
            ['value' => 'body_end', 'label' => __('End of page body')],
        ];
    }
}
