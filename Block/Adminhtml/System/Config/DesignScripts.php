<?php
/**
 * Copyright © Softaware Commerce. All rights reserved.
 */
declare(strict_types=1);

namespace Softaware\CookieConsent\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Softaware\CookieConsent\Block\Adminhtml\DesignScriptsNotice;

/**
 * Read-only config row: lists scripts in the design configuration that load without consent.
 */
class DesignScripts extends Field
{
    public function render(AbstractElement $element): string
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    protected function _getElementHtml(AbstractElement $element): string
    {
        return $this->getLayout()->createBlock(DesignScriptsNotice::class)
            ->setData('show_ok', true)
            ->setData('hide_settings_link', true)
            ->toHtml();
    }
}
