<?php
/**
 * Copyright © Softaware Commerce. All rights reserved.
 */
declare(strict_types=1);

namespace Softaware\CookieConsent\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\LocalizedException;

/**
 * Normalises a comma-separated list of ISO 3166-1 / 3166-2 region codes.
 */
class Regions extends Value
{
    /**
     * @return $this
     * @throws LocalizedException
     */
    public function beforeSave()
    {
        $codes = array_filter(array_map('trim', explode(',', strtoupper((string)$this->getValue()))));
        foreach ($codes as $code) {
            if (!preg_match('/^[A-Z]{2}(-[A-Z0-9]{1,3})?$/', $code)) {
                throw new LocalizedException(__('"%1" is not an ISO 3166 region code (examples: GB, DE, US-CA).', $code));
            }
        }
        $this->setValue(implode(', ', array_unique($codes)));
        return parent::beforeSave();
    }
}
