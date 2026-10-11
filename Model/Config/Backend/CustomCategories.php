<?php
/**
 * Copyright © CommerceGears. All rights reserved.
 */
declare(strict_types=1);

namespace CommerceGears\CookieConsent\Model\Config\Backend;

use Magento\Config\Model\Config\Backend\Serialized\ArraySerialized;
use Magento\Framework\Exception\LocalizedException;
use CommerceGears\CookieConsent\Model\Config;

/**
 * Validates the "Additional Categories" dynamic rows.
 */
class CustomCategories extends ArraySerialized
{
    private const ALLOWED_CONSENT_TYPES = [
        'ad_storage', 'ad_user_data', 'ad_personalization', 'analytics_storage',
        'functionality_storage', 'personalization_storage', 'security_storage',
    ];

    /**
     * @return $this
     * @throws LocalizedException
     */
    public function beforeSave()
    {
        $value = $this->getValue();
        if (is_array($value)) {
            $codes = [];
            foreach ($value as $key => $row) {
                if (!is_array($row)) {
                    unset($value[$key]);
                    continue;
                }
                $code = strtolower(trim((string)($row['code'] ?? '')));
                $label = trim((string)($row['label'] ?? ''));
                if ($code === '' && $label === '') {
                    unset($value[$key]);
                    continue;
                }
                if (!preg_match('/^[a-z][a-z0-9_]{1,31}$/', $code)) {
                    throw new LocalizedException(__(
                        'Cookie category code "%1" is invalid. Use 2-32 lower-case letters, digits or underscores, starting with a letter.',
                        $code
                    ));
                }
                if (in_array($code, Config::BUILT_IN_CATEGORIES, true) || isset($codes[$code])) {
                    throw new LocalizedException(__('Cookie category code "%1" is already used.', $code));
                }
                if ($label === '') {
                    throw new LocalizedException(__('Cookie category "%1" needs a name.', $code));
                }
                $types = array_filter(array_map('trim', explode(',', strtolower((string)($row['consent_types'] ?? '')))));
                foreach ($types as $type) {
                    if (!in_array($type, self::ALLOWED_CONSENT_TYPES, true)) {
                        throw new LocalizedException(__(
                            'Unknown Google consent type "%1". Allowed: %2.',
                            $type,
                            implode(', ', self::ALLOWED_CONSENT_TYPES)
                        ));
                    }
                }
                $codes[$code] = true;
                $value[$key] = [
                    'code' => $code,
                    'label' => $label,
                    'description' => trim((string)($row['description'] ?? '')),
                    'consent_types' => implode(',', array_unique($types)),
                ];
            }
            $this->setValue($value);
        }
        return parent::beforeSave();
    }
}
