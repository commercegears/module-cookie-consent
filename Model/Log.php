<?php
/**
 * Copyright © CommerceGears. All rights reserved.
 */
declare(strict_types=1);

namespace CommerceGears\CookieConsent\Model;

use Magento\Framework\Model\AbstractModel;

class Log extends AbstractModel
{
    protected function _construct(): void
    {
        $this->_init(ResourceModel\Log::class);
    }
}
