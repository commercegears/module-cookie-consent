<?php
/**
 * Copyright © Softaware Commerce. All rights reserved.
 */
declare(strict_types=1);

namespace Softaware\CookieConsent\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\LocalizedException;
use Softaware\CookieConsent\Model\Config;

/**
 * Validates "Cookies to Delete When Refused": lines of "category: name, prefix*, ...".
 */
class CookieList extends Value
{
    /** Cookies the shop needs; deleting them would log visitors out or empty their basket. */
    private const PROTECTED = [
        'phpsessid', 'form_key', 'sw_consent', 'private_content_version', 'x-magento-vary', 'section_data_ids',
        'store', 'persistent_shopping_cart', 'login_redirect', 'admin',
    ];

    /**
     * @return $this
     * @throws LocalizedException
     */
    public function beforeSave()
    {
        $lines = [];
        foreach (preg_split('/\R/', (string)$this->getValue()) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (!preg_match('/^([a-z][a-z0-9_]*)\s*:(.*)$/i', $line, $m)) {
                throw new LocalizedException(__(
                    'Cookies to delete: "%1" is not in the form "category: cookie, cookie_prefix*".',
                    $line
                ));
            }
            $code = strtolower($m[1]);
            if ($code === Config::CATEGORY_NECESSARY) {
                throw new LocalizedException(__('Cookies to delete: necessary cookies cannot be deleted.'));
            }
            $names = array_values(array_filter(array_map('trim', explode(',', $m[2]))));
            foreach ($names as $name) {
                $lower = strtolower(rtrim($name, '*'));
                if (!Config::isValidCookiePattern($name)) {
                    throw new LocalizedException(__(
                        'Cookies to delete: "%1" is not a valid cookie name. Use letters, digits, _ - . and an optional * at the end (at least two characters before it).',
                        $name
                    ));
                }
                foreach (self::PROTECTED as $protected) {
                    if ($lower === $protected || (str_ends_with($name, '*') && str_starts_with($protected, $lower))
                        || str_starts_with($lower, 'mage-')
                    ) {
                        throw new LocalizedException(__('Cookies to delete: "%1" is needed by the shop and cannot be deleted.', $name));
                    }
                }
            }
            $lines[] = $code . ': ' . implode(', ', $names);
        }
        $this->setValue(implode("\n", $lines));
        return parent::beforeSave();
    }
}
