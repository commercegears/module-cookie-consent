<?php
/**
 * Copyright © Softaware Commerce. All rights reserved.
 */
declare(strict_types=1);

namespace Softaware\CookieConsent\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Script extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('softaware_cookieconsent_script', 'script_id');
    }

    /**
     * Store ids may arrive as an array from the admin form.
     *
     * @param AbstractModel $object
     * @return $this
     */
    protected function _beforeSave(AbstractModel $object)
    {
        $storeIds = $object->getData('store_ids');
        if (is_array($storeIds)) {
            $storeIds = array_unique(array_map('intval', $storeIds));
            if (in_array(0, $storeIds, true) || $storeIds === []) {
                $storeIds = [0];
            }
            $object->setData('store_ids', implode(',', $storeIds));
        }
        return parent::_beforeSave($object);
    }
}
