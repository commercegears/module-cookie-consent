<?php
/**
 * Copyright © CommerceGears. All rights reserved.
 */
declare(strict_types=1);

namespace CommerceGears\CookieConsent\Plugin;

use Magento\Cookie\Helper\Cookie;
use CommerceGears\CookieConsent\Model\Config;

/**
 * Switches off Magento's cookie restriction notice (Luma and Hyvä) while this module handles consent.
 */
class DisableNativeCookieNotice
{
    public function __construct(private readonly Config $config)
    {
    }

    /**
     * @param Cookie $subject
     * @param mixed $result
     * @return mixed
     */
    public function afterIsCookieRestrictionModeEnabled(Cookie $subject, mixed $result): mixed
    {
        return $result && $this->config->isReplaceNativeNotice() ? false : $result;
    }
}
