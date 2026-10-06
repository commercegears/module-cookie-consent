<?php
/**
 * Copyright © Softaware Commerce. All rights reserved.
 */
declare(strict_types=1);

namespace Softaware\CookieConsent\Model;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

/**
 * Builds the inline script printed at the very top of <head>:
 * Google Consent Mode v2 defaults, the stored consent (if any) as an immediate update,
 * the softaware_consent_update dataLayer event and a small window.SwConsent stub API.
 */
class HeadScript
{
    public function __construct(
        private readonly Config $config,
        private readonly Json $json,
        private readonly SecureHtmlRenderer $secureRenderer
    ) {
    }

    /**
     * Data shared by the head script and the banner script (window.swConsentConfig).
     *
     * @return array<string, mixed>
     */
    public function getClientConfig(): array
    {
        $categories = $this->config->getCategories();
        return [
            'cookie' => Config::COOKIE_NAME,
            'version' => $this->config->getConsentVersion(),
            'expiryDays' => $this->config->getExpiryDays(),
            'categories' => array_column($categories, 'code'),
            'required' => [Config::CATEGORY_NECESSARY],
            'map' => $this->config->getConsentTypeMap(),
            'consentMode' => $this->config->isConsentModeEnabled(),
            'waitForUpdate' => $this->config->getWaitForUpdate(),
            'adsDataRedaction' => $this->config->isAdsDataRedaction(),
            'urlPassthrough' => $this->config->isUrlPassthrough(),
            'regions' => $this->config->getRegions(),
            'otherRegions' => $this->config->getOtherRegionsDefault(),
        ];
    }

    public function render(): string
    {
        if (!$this->config->isEnabled()) {
            return '';
        }
        $json = $this->json->serialize($this->getClientConfig());
        $js = <<<JS
(function (c) {
    var w = window, dl = w.dataLayer = w.dataLayer || [];
    if (typeof w.gtag !== 'function') { w.gtag = function () { dl.push(arguments); }; }
    w.swConsentConfig = c;
    function has(list, code) { return list.indexOf(code) !== -1; }
    function types(granted) {
        var s = {}, t;
        for (t in c.map) {
            if (Object.prototype.hasOwnProperty.call(c.map, t)) {
                s[t] = c.map[t].every(function (code) { return has(granted, code); }) ? 'granted' : 'denied';
            }
        }
        return s;
    }
    function read() {
        try {
            var m = document.cookie.match(new RegExp('(?:^|; )' + c.cookie + '=([^;]*)')), v;
            if (!m) { return null; }
            v = JSON.parse(decodeURIComponent(m[1]));
            if (!v || v.v !== c.version || !Array.isArray(v.c) || !v.ts || (Date.now() - v.ts * 1000) > c.expiryDays * 864e5) {
                return null;
            }
            v.c = v.c.filter(function (code) { return has(c.categories, code); });
            c.required.forEach(function (code) { if (!has(v.c, code)) { v.c.push(code); } });
            return v;
        } catch (e) { return null; }
    }
    var stored = read(), q = [], api;
    if (c.consentMode) {
        var d = types(c.required);
        if (c.waitForUpdate > 0) { d.wait_for_update = c.waitForUpdate; }
        if (c.regions.length) {
            if (c.otherRegions === 'granted') { w.gtag('consent', 'default', types(c.categories)); }
            d.region = c.regions;
        }
        w.gtag('consent', 'default', d);
        if (c.adsDataRedaction) { w.gtag('set', 'ads_data_redaction', true); }
        if (c.urlPassthrough) { w.gtag('set', 'url_passthrough', true); }
        if (stored) { w.gtag('consent', 'update', types(stored.c)); }
    }
    api = w.SwConsent = {
        config: c,
        state: stored,
        consentTypes: types,
        has: function (code) { return has(c.required, code) || (!!api.state && has(api.state.c, code)); },
        whenGranted: function (code, cb) { if (api.has(code)) { cb(); } else { q.push([code, cb]); } },
        _queue: q
    };
    if (stored) {
        var cats = {};
        c.categories.forEach(function (code) { cats[code] = has(stored.c, code) ? 'granted' : 'denied'; });
        dl.push({
            event: 'softaware_consent_update',
            sw_consent_trigger: 'stored',
            sw_consent: cats,
            sw_consent_categories: stored.c.join(','),
            sw_consent_version: stored.v
        });
    }
})({$json});
JS;
        return "\n" . $this->secureRenderer->renderTag('script', ['data-sw-consent-head' => '1'], $js, false) . "\n";
    }
}
