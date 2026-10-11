<?php
/**
 * Copyright © CommerceGears. All rights reserved.
 */
declare(strict_types=1);

namespace CommerceGears\CookieConsent\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\LocalizedException;

/**
 * Accepts #rgb / #rrggbb / #rrggbbaa colours only (the value is written into a style block).
 */
class Colour extends Value
{
    /**
     * @return $this
     * @throws LocalizedException
     */
    public function beforeSave()
    {
        $value = trim((string)$this->getValue());
        if ($value !== '' && !preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $value)) {
            throw new LocalizedException(__('"%1" is not a hex colour such as #1979c3.', $value));
        }
        $this->setValue(strtolower($value));
        return parent::beforeSave();
    }
}
