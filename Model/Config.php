<?php
/**
 * Copyright © Softaware Commerce. All rights reserved.
 */
declare(strict_types=1);

namespace Softaware\CookieConsent\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Typed access to the module configuration (section softaware_cookieconsent).
 */
class Config
{
    public const SECTION = 'softaware_cookieconsent';
    public const COOKIE_NAME = 'sw_consent';

    public const CATEGORY_NECESSARY = 'necessary';
    public const CATEGORY_PREFERENCES = 'preferences';
    public const CATEGORY_ANALYTICS = 'analytics';
    public const CATEGORY_MARKETING = 'marketing';
    public const BUILT_IN_CATEGORIES = [
        self::CATEGORY_NECESSARY,
        self::CATEGORY_PREFERENCES,
        self::CATEGORY_ANALYTICS,
        self::CATEGORY_MARKETING,
    ];

    /** Google Consent Mode v2 types owned by each built-in category. */
    public const CONSENT_TYPES = [
        self::CATEGORY_NECESSARY => ['security_storage'],
        self::CATEGORY_PREFERENCES => ['functionality_storage', 'personalization_storage'],
        self::CATEGORY_ANALYTICS => ['analytics_storage'],
        self::CATEGORY_MARKETING => ['ad_storage', 'ad_user_data', 'ad_personalization'],
    ];

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly StoreManagerInterface $storeManager,
        private readonly Json $json
    ) {
    }

    public function getValue(string $path, int|string|null $store = null): string
    {
        return (string)$this->scopeConfig->getValue(self::SECTION . '/' . $path, ScopeInterface::SCOPE_STORE, $store);
    }

    public function getFlag(string $path, int|string|null $store = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::SECTION . '/' . $path, ScopeInterface::SCOPE_STORE, $store);
    }

    public function isEnabled(int|string|null $store = null): bool
    {
        return $this->getFlag('general/enabled', $store);
    }

    public function getConsentVersion(int|string|null $store = null): int
    {
        return max(1, (int)$this->getValue('general/consent_version', $store));
    }

    public function getExpiryDays(int|string|null $store = null): int
    {
        return max(1, (int)$this->getValue('general/expiry_days', $store));
    }

    public function isReplaceNativeNotice(int|string|null $store = null): bool
    {
        return $this->isEnabled($store) && $this->getFlag('general/replace_native_notice', $store);
    }

    public function getPrivacyUrl(int|string|null $store = null): string
    {
        $url = trim($this->getValue('general/privacy_url', $store));
        if ($url === '') {
            return '';
        }
        if (preg_match('#^(https?:)?//#i', $url)) {
            return $url;
        }
        try {
            return rtrim($this->storeManager->getStore($store)->getBaseUrl(), '/') . '/' . ltrim($url, '/');
        } catch (\Exception) {
            return '/' . ltrim($url, '/');
        }
    }

    public function getReopenLinkMode(int|string|null $store = null): string
    {
        return $this->getValue('general/reopen_link', $store) ?: 'footer';
    }

    public function showFooterLink(int|string|null $store = null): bool
    {
        return in_array($this->getReopenLinkMode($store), ['footer', 'footer_floating'], true);
    }

    public function showFloatingButton(int|string|null $store = null): bool
    {
        return in_array($this->getReopenLinkMode($store), ['floating', 'footer_floating'], true);
    }

    public function getLayout(int|string|null $store = null): string
    {
        return $this->getValue('banner/layout', $store) === 'box' ? 'box' : 'bar';
    }

    public function getPosition(int|string|null $store = null): string
    {
        if ($this->getLayout($store) === 'box') {
            $position = $this->getValue('banner/box_position', $store);
            return in_array($position, ['bottom-left', 'bottom-right', 'center'], true) ? $position : 'bottom-left';
        }
        return $this->getValue('banner/bar_position', $store) === 'top' ? 'top' : 'bottom';
    }

    /**
     * Built-in plus custom categories visible to the visitor, in display order.
     *
     * @return array<int, array{code: string, label: string, description: string, required: bool, consent_types: string[]}>
     */
    public function getCategories(int|string|null $store = null): array
    {
        $categories = [[
            'code' => self::CATEGORY_NECESSARY,
            'label' => $this->getValue('categories/necessary_label', $store),
            'description' => $this->getValue('categories/necessary_description', $store),
            'required' => true,
            'consent_types' => self::CONSENT_TYPES[self::CATEGORY_NECESSARY],
        ]];
        if ($this->getFlag('categories/preferences_enabled', $store)) {
            $categories[] = $this->builtIn(self::CATEGORY_PREFERENCES, $store);
        }
        $categories[] = $this->builtIn(self::CATEGORY_ANALYTICS, $store);
        if ($this->getFlag('categories/marketing_enabled', $store)) {
            $categories[] = $this->builtIn(self::CATEGORY_MARKETING, $store);
        }
        foreach ($this->getCustomCategories($store) as $custom) {
            $categories[] = $custom;
        }
        return $categories;
    }

    /**
     * @return string[]
     */
    public function getCategoryCodes(int|string|null $store = null): array
    {
        return array_column($this->getCategories($store), 'code');
    }

    /**
     * @return array<int, array{code: string, label: string, description: string, required: bool, consent_types: string[]}>
     */
    public function getCustomCategories(int|string|null $store = null): array
    {
        $raw = $this->getValue('categories/custom', $store);
        if ($raw === '') {
            return [];
        }
        try {
            $rows = $this->json->unserialize($raw);
        } catch (\InvalidArgumentException) {
            return [];
        }
        $result = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            $code = (string)($row['code'] ?? '');
            if ($code === '' || in_array($code, self::BUILT_IN_CATEGORIES, true)) {
                continue;
            }
            $result[] = [
                'code' => $code,
                'label' => (string)($row['label'] ?? $code),
                'description' => (string)($row['description'] ?? ''),
                'required' => false,
                'consent_types' => array_values(array_filter(array_map('trim', explode(',', (string)($row['consent_types'] ?? ''))))),
            ];
        }
        return $result;
    }

    /**
     * Consent type => category codes that must all be accepted for the type to be granted.
     * Types of hidden built-in categories: preferences fall back to "necessary"; marketing types stay denied
     * unless a custom category is mapped to them.
     *
     * @return array<string, string[]>
     */
    public function getConsentTypeMap(int|string|null $store = null): array
    {
        $map = [];
        foreach (self::CONSENT_TYPES as $category => $types) {
            foreach ($types as $type) {
                $map[$type] = [$category];
            }
        }
        if (!$this->getFlag('categories/preferences_enabled', $store)) {
            foreach (self::CONSENT_TYPES[self::CATEGORY_PREFERENCES] as $type) {
                $map[$type] = [self::CATEGORY_NECESSARY];
            }
        }
        if (!$this->getFlag('categories/marketing_enabled', $store)) {
            foreach (self::CONSENT_TYPES[self::CATEGORY_MARKETING] as $type) {
                $map[$type] = [];
            }
        }
        foreach ($this->getCustomCategories($store) as $custom) {
            foreach ($custom['consent_types'] as $type) {
                $map[$type][] = $custom['code'];
            }
        }
        foreach ($map as $type => $categories) {
            if ($categories === []) {
                // Marketing is hidden and no custom category controls the type: never granted.
                $map[$type] = ['__never__'];
            }
        }
        return $map;
    }

    public function isConsentModeEnabled(int|string|null $store = null): bool
    {
        return $this->getFlag('consent_mode/enabled', $store);
    }

    public function getWaitForUpdate(int|string|null $store = null): int
    {
        return (int)$this->getValue('consent_mode/wait_for_update', $store);
    }

    public function isAdsDataRedaction(int|string|null $store = null): bool
    {
        return $this->getFlag('consent_mode/ads_data_redaction', $store);
    }

    public function isUrlPassthrough(int|string|null $store = null): bool
    {
        return $this->getFlag('consent_mode/url_passthrough', $store);
    }

    /**
     * @return string[]
     */
    public function getRegions(int|string|null $store = null): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $this->getValue('consent_mode/regions', $store)))));
    }

    public function getOtherRegionsDefault(int|string|null $store = null): string
    {
        return $this->getValue('consent_mode/other_regions_default', $store) === 'denied' ? 'denied' : 'granted';
    }

    /**
     * @return array<string, string> CSS custom property => value (only when custom colours/font are set)
     */
    public function getStyleVariables(int|string|null $store = null): array
    {
        $vars = [];
        if ($this->getFlag('appearance/custom_colours', $store)) {
            $map = [
                '--sw-cc-bg' => 'background',
                '--sw-cc-text' => 'text',
                '--sw-cc-btn-bg' => 'button_background',
                '--sw-cc-btn-text' => 'button_text',
                '--sw-cc-link' => 'link',
            ];
            foreach ($map as $var => $field) {
                $value = $this->getValue('appearance/' . $field, $store);
                if (preg_match('/^#[0-9a-f]{3,8}$/i', $value)) {
                    $vars[$var] = $value;
                }
            }
        }
        $font = $this->getValue('appearance/font_family', $store);
        if ($font !== '' && preg_match('/^[a-zA-Z0-9 ,\'"\-_]{1,200}$/', $font)) {
            $vars['--sw-cc-font'] = $font;
        }
        return $vars;
    }

    public function hasCustomColours(int|string|null $store = null): bool
    {
        return $this->getFlag('appearance/custom_colours', $store);
    }

    public function isLogEnabled(int|string|null $store = null): bool
    {
        return $this->getFlag('log/enabled', $store);
    }

    public function isLogAnonymiseIp(int|string|null $store = null): bool
    {
        return $this->getFlag('log/anonymise_ip', $store);
    }

    public function getLogRetentionDays(): int
    {
        return (int)$this->scopeConfig->getValue(self::SECTION . '/log/retention_days');
    }

    /**
     * @return array{code: string, label: string, description: string, required: bool, consent_types: string[]}
     */
    private function builtIn(string $code, int|string|null $store): array
    {
        return [
            'code' => $code,
            'label' => $this->getValue('categories/' . $code . '_label', $store),
            'description' => $this->getValue('categories/' . $code . '_description', $store),
            'required' => false,
            'consent_types' => self::CONSENT_TYPES[$code],
        ];
    }
}
