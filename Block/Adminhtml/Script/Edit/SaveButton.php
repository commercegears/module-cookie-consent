<?php
/**
 * Copyright © Softaware Commerce. All rights reserved.
 */
declare(strict_types=1);

namespace Softaware\CookieConsent\Block\Adminhtml\Script\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class SaveButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getButtonData(): array
    {
        return [
            'label' => __('Save'),
            // Empty on_click: without it Magento adds a link to the current route (location.href) that races the save.
            'on_click' => '',
            'class' => 'save primary',
            'data_attribute' => [
                'mage-init' => ['button' => ['event' => 'save']],
                'form-role' => 'save',
            ],
            'sort_order' => 90,
        ];
    }
}
