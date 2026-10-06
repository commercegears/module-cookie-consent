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
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Turns HTML snippets into consent-gated markup:
 *  - <script> becomes <script type="text/plain" data-sw-consent="category"> (original type kept in data-sw-type),
 *  - img/iframe/source/embed/video/audio sources (src, srcset) become data-sw-src / data-sw-srcset,
 *  - <link rel="stylesheet" href> from another host becomes data-sw-href (the shop's own stylesheets are kept).
 * The storefront script (consent.js) restores them once the category is accepted.
 *
 * A tag that already carries data-sw-consent="code" is gated by that category instead;
 * data-sw-consent="necessary" leaves the tag running without consent.
 * Content inside <noscript> is left untouched. When the module is disabled the HTML is returned unchanged.
 *
 * Usage from other modules: inject this class (or the ViewModel\ScriptBlocker view model) and call
 * block($html, 'analytics'), or print attributes($category) inside your own <script> tag.
 */
class ScriptBlocker
{
    private const JS_TYPES = ['', 'text/javascript', 'application/javascript', 'module', 'text/ecmascript'];

    /** Tag => [attribute => CSP directive(s)] for resources that load on their own. */
    private const RESOURCE_TAGS = [
        'img' => ['src' => ['img-src'], 'srcset' => ['img-src']],
        'iframe' => ['src' => ['frame-src']],
        'source' => ['src' => ['media-src', 'img-src'], 'srcset' => ['img-src']],
        'video' => ['src' => ['media-src']],
        'audio' => ['src' => ['media-src']],
        'embed' => ['src' => ['object-src']],
        'link' => ['href' => ['style-src']],
    ];

    private const ATTRIBUTE_PATTERN = '#(\s+)([^\s"\'>/=]+)(\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s"\'>]+))?#';

    /** @var string[]|null */
    private ?array $ownHosts = null;

    public function __construct(
        private readonly Config $config,
        private readonly DynamicCollector $cspCollector,
        private readonly ConfigCollector $cspConfigCollector,
        private readonly CspNonceProvider $cspNonceProvider,
        private readonly StoreManagerInterface $storeManager
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

    /**
     * @param bool $foreignResourcesOnly true: images, iframes and media from the shop's own hosts keep loading
     *                                   (used for the design configuration, which often holds logos)
     */
    public function block(string $html, string $category, bool $foreignResourcesOnly = false): string
    {
        if (!$this->isActive() || trim($html) === '') {
            return $html;
        }
        $category = $this->normalise($category);
        if ($category === '' || $category === Config::CATEGORY_NECESSARY) {
            return $html;
        }
        return $this->eachOutsideNoscript(
            $html,
            fn (string $part): string => $this->blockFragment($part, $category, $foreignResourcesOnly)
        );
    }

    /**
     * Describes the tags in $html that would run or load without consent (for the admin detector).
     *
     * @return string[] e.g. ["script from widget.example.com", "inline script: window.foo = ..."]
     */
    public function findUngated(string $html): array
    {
        $found = [];
        if (trim($html) === '') {
            return $found;
        }
        $this->eachOutsideNoscript($html, function (string $part) use (&$found): string {
            preg_match_all('#<script\b([^>]*)>(.*?)(?:</script\s*>|$)#is', $part, $scripts, PREG_SET_ORDER);
            foreach ($scripts as $m) {
                $attributes = $this->parseAttributes($m[1]);
                if (!in_array(strtolower(trim($attributes['type'] ?? '')), self::JS_TYPES, true)
                    || $this->ownCategory($attributes) === Config::CATEGORY_NECESSARY
                ) {
                    continue;
                }
                if (isset($attributes['src'])) {
                    $found[] = (string)__('script from %1', $this->hostOf($attributes['src']) ?: $attributes['src']);
                } elseif (trim($m[2]) !== '') {
                    $code = trim((string)preg_replace('/\s+/', ' ', $m[2]));
                    $found[] = (string)__('inline script: %1', mb_strimwidth($code, 0, 70, '…'));
                }
            }
            preg_match_all('#<(iframe|img|embed)\b([^>]*)>#i', $part, $tags, PREG_SET_ORDER);
            foreach ($tags as $m) {
                $attributes = $this->parseAttributes($m[2]);
                if (isset($attributes['src']) && $this->hasForeignUrl([$attributes['src']])
                    && $this->ownCategory($attributes) !== Config::CATEGORY_NECESSARY
                ) {
                    $found[] = (string)__('%1 from %2', strtolower($m[1]), $this->hostOf($attributes['src']));
                }
            }
            return $part;
        });
        return array_values(array_unique($found));
    }

    /**
     * @param callable(string): string $callback
     */
    private function eachOutsideNoscript(string $html, callable $callback): string
    {
        $parts = preg_split('#(<noscript\b.*?</noscript\s*>)#is', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            return $html;
        }
        foreach ($parts as $i => $part) {
            if ($i % 2 === 0) {
                $parts[$i] = $callback($part);
            }
        }
        return implode('', $parts);
    }

    private function blockFragment(string $html, string $category, bool $foreignResourcesOnly): string
    {
        // Opening tags only, so a script without its closing tag is still blocked.
        $html = (string)preg_replace_callback(
            '#<script\b([^>]*)>#i',
            function (array $m) use ($category): string {
                $attributes = $this->parseAttributes($m[1]);
                $type = strtolower(trim($attributes['type'] ?? ''));
                $own = $this->ownCategory($attributes);
                if (!in_array($type, self::JS_TYPES, true) || $own === Config::CATEGORY_NECESSARY) {
                    return $m[0];
                }
                $rest = $this->rewriteAttributes($m[1], ['type' => null, 'data-sw-consent' => null]);
                if ($type === 'module') {
                    $rest .= ' data-sw-type="module"';
                }
                if (isset($attributes['src'])) {
                    $this->allowHost('script-src', $attributes['src']);
                } elseif (!isset($attributes['nonce'])) {
                    $rest .= $this->nonceAttribute();
                }
                return '<script type="text/plain" data-sw-consent="'
                    . htmlspecialchars($own ?? $category, ENT_QUOTES) . '"' . $rest . '>';
            },
            $html
        );
        return (string)preg_replace_callback(
            '#<(' . implode('|', array_keys(self::RESOURCE_TAGS)) . ')\b([^>]*)>#i',
            function (array $m) use ($category, $foreignResourcesOnly): string {
                $tag = strtolower($m[1]);
                $attributes = $this->parseAttributes($m[2]);
                $own = $this->ownCategory($attributes);
                if ($own === Config::CATEGORY_NECESSARY
                    || ($tag === 'link' && !preg_match('/\bstylesheet\b/i', $attributes['rel'] ?? ''))
                ) {
                    return $m[0];
                }
                $rename = ['data-sw-consent' => null];
                $gated = false;
                foreach (self::RESOURCE_TAGS[$tag] as $name => $directives) {
                    if (!isset($attributes[$name]) || trim($attributes[$name]) === '') {
                        continue;
                    }
                    $urls = $name === 'srcset'
                        ? array_map(static fn (string $c): string => (string)strtok(trim($c), ' '), explode(',', $attributes[$name]))
                        : [$attributes[$name]];
                    if (($foreignResourcesOnly || $tag === 'link') && !$this->hasForeignUrl($urls)) {
                        continue;
                    }
                    $rename[$name] = 'data-sw-' . $name;
                    $gated = true;
                    foreach ($urls as $url) {
                        foreach ($directives as $directive) {
                            $this->allowHost($directive, $url);
                        }
                    }
                }
                if (!$gated) {
                    return $m[0];
                }
                return '<' . $m[1] . ' data-sw-consent="' . htmlspecialchars($own ?? $category, ENT_QUOTES) . '"'
                    . $this->rewriteAttributes($m[2], $rename) . '>';
            },
            $html
        );
    }

    /**
     * @param array<string, string> $attributes
     * @return string|null category set on the tag itself (data-sw-consent), if any
     */
    private function ownCategory(array $attributes): ?string
    {
        if (!isset($attributes['data-sw-consent'])) {
            return null;
        }
        $own = $this->normalise($attributes['data-sw-consent']);
        return $own === '' ? null : $own;
    }

    /**
     * @return array<string, string> lower-case attribute name => decoded value
     */
    private function parseAttributes(string $attributes): array
    {
        $result = [];
        preg_match_all(self::ATTRIBUTE_PATTERN, $attributes, $matches, PREG_SET_ORDER);
        foreach ($matches as $m) {
            $name = strtolower($m[2]);
            $value = isset($m[3]) ? (string)preg_replace('#^\s*=\s*#', '', $m[3]) : '';
            $value = trim($value, '"\'');
            $result[$name] ??= html_entity_decode($value, ENT_QUOTES | ENT_HTML5);
        }
        return $result;
    }

    /**
     * Renames (or removes, when the new name is null) attributes, keeping everything else byte for byte.
     *
     * @param array<string, string|null> $map
     */
    private function rewriteAttributes(string $attributes, array $map): string
    {
        return (string)preg_replace_callback(
            self::ATTRIBUTE_PATTERN,
            static function (array $m) use ($map): string {
                $name = strtolower($m[2]);
                if (!array_key_exists($name, $map)) {
                    return $m[0];
                }
                return $map[$name] === null ? '' : $m[1] . $map[$name] . ($m[3] ?? '');
            },
            $attributes
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
     * @param string[] $urls
     */
    private function hasForeignUrl(array $urls): bool
    {
        foreach ($urls as $url) {
            $host = strtolower($this->hostOf(trim($url)));
            if ($host !== '' && !in_array($host, $this->getOwnHosts(), true)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Hosts of the current store's base, media and static URLs (secure and unsecure).
     *
     * @return string[]
     */
    private function getOwnHosts(): array
    {
        if ($this->ownHosts === null) {
            $this->ownHosts = [];
            try {
                $store = $this->storeManager->getStore();
                foreach ([UrlInterface::URL_TYPE_WEB, UrlInterface::URL_TYPE_MEDIA, UrlInterface::URL_TYPE_STATIC] as $type) {
                    foreach ([false, true] as $secure) {
                        $this->ownHosts[] = strtolower($this->hostOf((string)$store->getBaseUrl($type, $secure)));
                    }
                }
            } catch (\Throwable) {
                // No store context: treat every absolute URL as foreign.
            }
            $this->ownHosts = array_values(array_unique(array_filter($this->ownHosts)));
        }
        return $this->ownHosts;
    }

    private function hostOf(string $url): string
    {
        $host = parse_url(str_starts_with($url, '//') ? 'https:' . $url : $url, PHP_URL_HOST);
        return is_string($host) ? $host : '';
    }

    /**
     * Whitelist the remote host of a blocked resource so it can load once consent is given.
     */
    private function allowHost(string $policyId, string $url): void
    {
        $host = $this->hostOf(trim($url));
        if ($host === '' || !preg_match('/^[a-z0-9.\-]+$/i', $host)) {
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
