<?php
/**
 * Copyright © CommerceGears. All rights reserved.
 */
declare(strict_types=1);

namespace CommerceGears\CookieConsent\Model\ResourceModel\Script;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use CommerceGears\CookieConsent\Model\ResourceModel\Script as ScriptResource;
use CommerceGears\CookieConsent\Model\Script;

class Collection extends AbstractCollection
{
    /** @var string */
    protected $_idFieldName = 'script_id';

    protected function _construct(): void
    {
        $this->_init(Script::class, ScriptResource::class);
    }
}
