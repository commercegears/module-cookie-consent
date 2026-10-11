<?php
/**
 * Copyright © CommerceGears. All rights reserved.
 */
declare(strict_types=1);

namespace CommerceGears\CookieConsent\Model\ResourceModel\Log;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use CommerceGears\CookieConsent\Model\Log;
use CommerceGears\CookieConsent\Model\ResourceModel\Log as LogResource;

class Collection extends AbstractCollection
{
    /** @var string */
    protected $_idFieldName = 'log_id';

    protected function _construct(): void
    {
        $this->_init(Log::class, LogResource::class);
    }
}
