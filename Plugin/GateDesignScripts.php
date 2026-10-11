<?php
/**
 * Copyright © CommerceGears. All rights reserved.
 */
declare(strict_types=1);

namespace CommerceGears\CookieConsent\Plugin;

use Magento\Framework\View\Page\Config as PageConfig;
use Magento\Theme\Block\Html\Footer;
use Psr\Log\LoggerInterface;
use CommerceGears\CookieConsent\Model\Config;
use CommerceGears\CookieConsent\Model\ScriptBlocker;

/**
 * Gates the scripts that merchants paste into Content > Design > Configuration
 * (HTML Head > Scripts and Style Sheets, Footer > Miscellaneous HTML) behind the category chosen in
 * Stores > Configuration > CommerceGears > Cookie Consent > General > Scripts in Design Configuration.
 * Works on Luma and Hyvä (both render these two values through the same core methods).
 */
class GateDesignScripts
{
    public function __construct(
        private readonly Config $config,
        private readonly ScriptBlocker $blocker,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param PageConfig $subject
     * @param string|null $result
     * @return string|null
     */
    public function afterGetIncludes(PageConfig $subject, $result)
    {
        return $this->gate($result);
    }

    /**
     * @param Footer $subject
     * @param string|null $result
     * @return string|null
     */
    public function afterGetMiscellaneousHtml(Footer $subject, $result)
    {
        return $this->gate($result);
    }

    /**
     * The footer block is kept in the block cache: vary it by the chosen category.
     *
     * @param Footer $subject
     * @param array<int|string, mixed> $result
     * @return array<int|string, mixed>
     */
    public function afterGetCacheKeyInfo(Footer $subject, $result)
    {
        if (is_array($result)) {
            try {
                $result['sw_cc_design'] = $this->config->isEnabled() ? $this->config->getDesignScriptsCategory() : '';
            } catch (\Throwable) {
                // keep the core cache key
            }
        }
        return $result;
    }

    private function gate(mixed $html): mixed
    {
        if (!is_string($html) || $html === '') {
            return $html;
        }
        try {
            $category = $this->config->isEnabled() ? $this->config->getDesignScriptsCategory() : '';
            return $category === '' ? $html : $this->blocker->block($html, $category, true);
        } catch (\Throwable $e) {
            $this->logger->error('CommerceGears_CookieConsent: could not gate design scripts: ' . $e->getMessage());
            return $html;
        }
    }
}
