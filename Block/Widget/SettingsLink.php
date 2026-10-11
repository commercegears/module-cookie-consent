<?php
/**
 * Copyright © CommerceGears. All rights reserved.
 */
declare(strict_types=1);

namespace CommerceGears\CookieConsent\Block\Widget;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Widget\Block\BlockInterface;
use CommerceGears\CookieConsent\Model\Config;

/**
 * CMS widget "CommerceGears Cookie Settings Link".
 */
class SettingsLink extends Template implements BlockInterface
{
    /** @var string */
    protected $_template = 'CommerceGears_CookieConsent::widget/link.phtml';

    public function __construct(Context $context, private readonly Config $config, array $data = [])
    {
        parent::__construct($context, $data);
    }

    public function isConsentEnabled(): bool
    {
        return $this->config->isEnabled();
    }

    public function getLabel(): string
    {
        $label = trim((string)$this->getData('label'));
        return $label !== '' ? $label : $this->config->getValue('banner/reopen_label');
    }

    public function getCssClass(): string
    {
        return trim('sw-cc-settings-link ' . (string)$this->getData('css_class'));
    }
}
