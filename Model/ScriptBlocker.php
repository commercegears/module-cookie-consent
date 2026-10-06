<?php
/**
 * Copyright © Softaware Commerce. All rights reserved.
 */
declare(strict_types=1);

namespace Softaware\CookieConsent\Model;

use Magento\Csp\Helper\CspNonceProvider;
use Magento\Csp\Model\Collector\ConfigCollector;
use Magento\Csp\Model\Collector\DynamicCollector;
use Magento\Csp\Model\Policy\FetchPolicy;

/**
 * Turns HTML snippets into consent-gated markup:
 *  - <script> becomes <script type="text/plain" data-sw-consent="category"> (original type kept in data-sw-type),
 *  - <iframe src> and <img src> get data-sw-src instead of src.
 * The storefront script (consent.js) restores them once the category is accepted.
 * Content inside <noscript> is left untouched. When the module is disabled the HTML is returned unchanged.
 *
 * Usage from other modules: inject this class (or the ViewModel\ScriptBlocker view model) and call
 * block($html, 'analytics'), or print attributes($category) inside your own <script> tag.
 */
class ScriptBlocker
{
    private const JS_TYPES = ['', 'text/javascript', 'application/javascript', 'module', 'text/ecmascript'];

    public function __construct(
        private readonly Config $config,
        private readonly DynamicCollector $cspCollector,
        private readonly ConfigCollector $cspConfigCollector,
        private readonly CspNonceProvider $cspNonceProvider
    ) {
    }

    public function isActive(): bool
    {
        return $this->config->isEnabled();
    }

    /**
     * Attributes for a hand-written blocked script tag: <script <?= $blocker->attributes('marketing') ?>>.
     */
    public function attributes(string $category): string
    {
        if (!$this->isActive()) {
            return '';
        }
        return 'type="text/plain" data-sw-consent="' . htmlspecialchars($this->normalise($category), ENT_QUOTES) . '"';
    }

    public function block(string $html, string $category): string
    {
        if (!$this->isActive() || trim($html) === '') {
            return $html;
        }
        $category = $this->normalise($category);
        $parts = preg_split('#(<noscript\b.*?</noscript>)#is', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            return $html;
        }
        foreach ($parts as $i => $part) {
            if ($i % 2 === 1) {
                continue; // noscript block
            }
            $parts[$i] = $this->blockFragment($part, $category);
        }
        return implode('', $parts);
    }

    private function blockFragment(string $html, string $category): string
    {
        $attr = ' data-sw-consent="' . htmlspecialchars($category, ENT_QUOTES) . '"';
        $html = (string)preg_replace_callback(
            '#<script\b([^>]*)>(.*?)</script>#is',
            function (array $m) use ($attr): string {
                $attributes = $m[1];
                $type = '';
                if (preg_match('#\stype\s*=\s*(["\'])(.*?)\1#i', $attributes, $t)
                    || preg_match('#\stype\s*=\s*([^\s>]+)#i', $attributes, $t)
                ) {
                    $type = strtolower(trim(end($t)));
                }
                if (!in_array($type, self::JS_TYPES, true) || str_contains($attributes, 'data-sw-consent')) {
                    return $m[0];
                }
                $attributes = (string)preg_replace('#\stype\s*=\s*(["\']).*?\1|\stype\s*=\s*[^\s>]+#i', '', $attributes);
                if ($type === 'module') {
                    $attributes .= ' data-sw-type="module"';
                }
                if (preg_match('#\ssrc\s*=\s*["\']?([^"\'\s>]+)#i', $attributes, $src)) {
                    $this->allowHost('script-src', $src[1]);
                } elseif (trim($m[2]) !== '' && !str_contains($attributes, 'nonce=')) {
                    $attributes .= $this->nonceAttribute();
                }
                return '<script type="text/plain"' . $attr . $attributes . '>' . $m[2] . '</script>';
            },
            $html
        );
        return (string)preg_replace_callback(
            '#<(iframe|img)\b([^>]*?)\ssrc\s*=(\s*["\']?)([^"\'\s>]*)#i',
            function (array $m) use ($attr): string {
                if (str_contains($m[2], 'data-sw-consent')) {
                    return $m[0];
                }
                $this->allowHost(strtolower($m[1]) === 'img' ? 'img-src' : 'frame-src', $m[4]);
                return '<' . $m[1] . $attr . $m[2] . ' data-sw-src=' . $m[3] . $m[4];
            },
            $html
        );
    }

    /**
     * On pages where the CSP forbids inline scripts (checkout), give the blocked inline script a nonce;
     * consent.js copies it to the re-created script element.
     */
    private function nonceAttribute(): string
    {
        try {
            foreach ($this->cspConfigCollector->collect() as $policy) {
                if ($policy instanceof FetchPolicy && $policy->getId() === 'script-src') {
                    return $policy->isInlineAllowed()
                        ? ''
                        : ' nonce="' . htmlspecialchars($this->cspNonceProvider->generateNonce(), ENT_QUOTES) . '"';
                }
            }
        } catch (\Throwable) {
            // CSP module not available.
        }
        return '';
    }

    /**
     * Whitelist the remote host of a blocked resource so it can load once consent is given.
     */
    private function allowHost(string $policyId, string $url): void
    {
        $host = parse_url(str_starts_with($url, '//') ? 'https:' . $url : $url, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            return;
        }
        try {
            $this->cspCollector->add(new FetchPolicy($policyId, false, [$host]));
        } catch (\Throwable) {
            // CSP module not available.
        }
    }

    private function normalise(string $category): string
    {
        return (string)preg_replace('/[^a-z0-9_ ,]/', '', strtolower($category));
    }
}
