<?php
/**
 * Copyright © Softaware Commerce. All rights reserved.
 */
declare(strict_types=1);

namespace Softaware\CookieConsent\Model\ResourceModel\Log;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Softaware\CookieConsent\Model\Log;
use Softaware\CookieConsent\Model\ResourceModel\Log as LogResource;

class Collection extends AbstractCollection
{
    /** @var string */
    protected $_idFieldName = 'log_id';

    protected function _construct(): void
    {
        $this->_init(Log::class, LogResource::class);
    }
}
