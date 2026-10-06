<?php
/**
 * Copyright © Softaware Commerce. All rights reserved.
 */
declare(strict_types=1);

namespace Softaware\CookieConsent\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\LocalizedException;

/**
 * Restricts the font-family value to characters that are safe inside a style block.
 */
class FontFamily extends Value
{
    /**
     * @return $this
     * @throws LocalizedException
     */
    public function beforeSave()
    {
        $value = trim((string)$this->getValue());
        if ($value !== '' && !preg_match('/^[a-zA-Z0-9 ,\'"\-_]{1,200}$/', $value)) {
            throw new LocalizedException(__('The font family may only contain letters, digits, spaces, quotes, commas and hyphens.'));
        }
        $this->setValue($value);
        return parent::beforeSave();
    }
}
