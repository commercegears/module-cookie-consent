<?php
/**
 * Copyright © Softaware Commerce. All rights reserved.
 */
declare(strict_types=1);

namespace Softaware\CookieConsent\ViewModel;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Softaware\CookieConsent\Model\Config;

/**
 * Data for the banner / preferences templates (Luma and Hyvä). Everything here is store-level config,
 * so the output is safe to keep in the full-page cache.
 */
class Banner implements ArgumentInterface
{
    private const ALLOWED_TAGS = ['b', 'strong', 'em', 'i', 'a', 'br'];

    public function __construct(
        private readonly Config $config,
        private readonly Escaper $escaper,
        private readonly Json $json,
        private readonly UrlInterface $urlBuilder,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->config->isEnabled();
    }

    public function getLayout(): string
    {
        return $this->config->getLayout();
    }

    public function getPosition(): string
    {
        return $this->config->getPosition();
    }

    public function text(string $path): string
    {
        return $this->config->getValue($path);
    }

    /**
     * Admin-entered rich text with a small tag whitelist.
     */
    public function richText(string $path): string
    {
        return nl2br($this->escaper->escapeHtml($this->config->getValue($path), self::ALLOWED_TAGS), false);
    }

    public function showReject(): bool
    {
        return $this->config->getFlag('banner/show_reject');
    }

    public function showFloatingButton(): bool
    {
        return $this->config->showFloatingButton();
    }

    public function showFooterLink(): bool
    {
        return $this->config->isEnabled() && $this->config->showFooterLink();
    }

    public function getPrivacyUrl(): string
    {
        return $this->config->getPrivacyUrl();
    }

    /**
     * @return array<int, array{code: string, label: string, description: string, required: bool, consent_types: string[]}>
     */
    public function getCategories(): array
    {
        return $this->config->getCategories();
    }

    public function hasCustomStyle(): bool
    {
        return $this->config->getStyleVariables() !== [];
    }

    public function hasCustomColours(): bool
    {
        return $this->config->hasCustomColours();
    }

    /**
     * Inline CSS variables (values are validated hex colours / font names, see Config::getStyleVariables()).
     */
    public function getStyleVariables(): string
    {
        $css = '';
        foreach ($this->config->getStyleVariables() as $name => $value) {
            $css .= $name . ':' . $value . ';';
        }
        return $css;
    }

    /**
     * Banner-only settings for consent.js (the head script exposes the shared ones as window.swConsentConfig).
     */
    public function getJsConfig(): string
    {
        $store = $this->storeManager->getStore();
        $secure = (bool)$store->isCurrentlySecure() || str_starts_with((string)$store->getBaseUrl(), 'https');
        return $this->json->serialize([
            'logUrl' => $this->config->isLogEnabled() ? $this->urlBuilder->getUrl('swconsent/log/save') : '',
            'cookieDomain' => (string)$this->scopeConfig->getValue('web/cookie/cookie_domain', ScopeInterface::SCOPE_STORE),
            'cookiePath' => (string)$this->scopeConfig->getValue('web/cookie/cookie_path', ScopeInterface::SCOPE_STORE) ?: '/',
            'secure' => $secure,
        ]);
    }
}
