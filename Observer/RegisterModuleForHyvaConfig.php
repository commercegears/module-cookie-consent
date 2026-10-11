<?php
/**
 * Copyright © CommerceGears. All rights reserved.
 */
declare(strict_types=1);

namespace CommerceGears\CookieConsent\Observer;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Adds this module to the Hyvä Tailwind source scan.
 */
class RegisterModuleForHyvaConfig implements ObserverInterface
{
    public function __construct(private readonly ComponentRegistrar $componentRegistrar)
    {
    }

    public function execute(Observer $observer): void
    {
        $config = $observer->getData('config');
        $extensions = $config->hasData('extensions') ? $config->getData('extensions') : [];
        $path = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, 'CommerceGears_CookieConsent');
        $extensions[] = ['src' => substr($path, strlen(BP) + 1)];
        $config->setData('extensions', $extensions);
    }
}
