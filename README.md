# Softaware Cookie Consent for Magento 2

`softaware/module-cookie-consent` (`Softaware_CookieConsent`)

A cookie banner and preferences centre with Google Consent Mode v2, consent-gated third-party scripts and an optional
consent log. Works on Luma (and Blank-based themes) and Hyvä, with full-page cache.

## Features

- **Banner**: full-width bar (top/bottom) or box (bottom left/right, centre). "Accept all", "Reject all" (can be hidden,
  not recommended in the UK/EU) and "Manage preferences" buttons, optional policy link.
- **Preferences window**: one switch per category. Built-in categories: Necessary (always on), Preferences, Analytics,
  Marketing. Preferences and Marketing can be hidden. Add your own categories (code, name, description, Google consent
  types). All texts are store-view settings.
- **Change of mind**: "Cookie settings" link in the footer, a floating button, the CMS widget
  *Softaware Cookie Settings Link*, or any link to `#cookie-settings` (or element with `data-sw-cc-open`).
- **Storage**: first-party cookie `sw_consent` = `{"v": version, "id": random UUID, "ts": unix time, "c": [categories]}`.
  Increase the consent version to ask everybody again; consent expires after the configured number of days.
  Withdrawing a category reloads the page so scripts that already ran are gone.
- **Google Consent Mode v2**: `gtag('consent', 'default', …)` is printed directly after `<title>`, before any CSS, JS or
  tag manager. Optional regions (ISO 3166-2), `wait_for_update`, `ads_data_redaction`, `url_passthrough`. Returning
  visitors get `gtag('consent', 'update', …)` immediately in the head, so the first hit is already correct.
  Mapping: Necessary → `security_storage`; Preferences → `functionality_storage`, `personalization_storage`;
  Analytics → `analytics_storage`; Marketing → `ad_storage`, `ad_user_data`, `ad_personalization`.
- **dataLayer event** on every page with stored consent and on every choice:
  ```js
  {event: 'softaware_consent_update', sw_consent_trigger: 'stored'|'user',
   sw_consent: {necessary: 'granted', analytics: 'denied', ...}, sw_consent_categories: 'necessary,analytics', sw_consent_version: 1}
  ```
- **Consent-gated scripts** (Softaware > Cookie Consent > Third-party Scripts): paste a vendor snippet, choose category,
  position (head / end of body) and store views. `<script>` tags are rendered as `type="text/plain" data-sw-consent="…"`,
  `img`/`iframe`/`source`/`video`/`audio`/`embed` sources as `data-sw-src` / `data-sw-srcset` and external stylesheets
  as `data-sw-href`, and are activated in order once the category is accepted. Remote hosts are added to the CSP; inline
  scripts get a CSP nonce on pages with a strict CSP (checkout). A snippet with an unclosed `<script>` tag is refused.
  On a single tag, `data-sw-consent="necessary"` keeps it running without consent and `data-sw-consent="marketing"` uses
  that category instead of the snippet's.
- **Scripts in Magento's design configuration** (chat widgets, pixels pasted into *Content > Design > Configuration >
  HTML Head > Scripts and Style Sheets* or *Footer > Miscellaneous HTML*): set *General > Scripts in Design Configuration*
  to a category and they wait for consent too (images, iframes and stylesheets from the shop's own domain keep loading).
  The settings page and the Third-party Scripts grid list every such script that currently loads without consent.
- **Cookie clean-up**: first-party cookies of refused or withdrawn categories are deleted in the browser
  (*Cookie Categories > Cookies to Delete When Refused*, pre-filled for Google Analytics, Clarity, Hotjar, Meta, Google
  Ads, Microsoft Ads, TikTok and Pinterest). Shop cookies (session, form key, basket) are never deleted.
- **Consent log** (optional): random consent id, store, choice, categories, version and an HMAC-SHA256 of the (optionally
  truncated) IP. Admin grid with filters, CSV/XML export, mass delete; daily retention cron. At most 60 entries per
  (truncated) IP and hour are stored, so the public logging endpoint cannot be used to flood the table.
- **Magento's own cookie notice** ("Cookie Restriction Mode") is switched off while the module is enabled (setting
  *Replace Magento Cookie Restriction Notice*), on Luma and Hyvä.
- **Appearance**: uses the theme's fonts and buttons (Luma `.action.primary`, Hyvä `btn btn-primary`) by default;
  optional custom colours and font.
- **Accessibility**: real buttons, labelled switches (`role="switch"`), modal dialog with `aria-modal`, focus trap,
  Escape to close, focus returned to the opener, visible focus styles.

## Configuration

*Stores > Configuration > Softaware > Cookie Consent* (also *Softaware > Cookie Consent > Settings*).

| Group | Notes |
|---|---|
| General | Enabled, consent version, expiry, replace Magento notice, policy URL, "Cookie settings" link (footer / floating / both / none), scripts in design configuration (+ list of ungated scripts found) |
| Banner | Layout and position, show "Reject all", texts |
| Preferences Window | Texts |
| Cookie Categories | Names/descriptions, show Preferences/Marketing, additional categories, cookies to delete when refused |
| Google Consent Mode v2 | On/off, wait_for_update, ads_data_redaction, url_passthrough, regions, default outside those regions |
| Appearance | Custom colours, font family |
| Consent Log | On/off, truncate IP before hashing, retention days |

Admin pages: *Softaware > Cookie Consent > Third-party Scripts*, *Consent Log*. ACL: `Softaware_CookieConsent::scripts`,
`::log`, `::log_delete`, `::config`. Note that `::scripts` lets a user add JavaScript to every storefront page, including
checkout: give it only to people you would also let edit *Content > Design > Configuration*.

## For developers

JavaScript (`window.SwConsent`, available from the `<head>` on):

```js
SwConsent.has('analytics');                       // boolean
SwConsent.whenGranted('marketing', function () { /* load pixel */ });
SwConsent.onChange(function (detail) { /* detail.categories, detail.trigger */ });
SwConsent.open(); SwConsent.acceptAll(); SwConsent.rejectAll(); SwConsent.save(['necessary', 'analytics']);
document.addEventListener('sw-consent:change', function (e) { console.log(e.detail); });
```

Markup that waits for consent:

```html
<script type="text/plain" data-sw-consent="analytics">/* runs after consent */</script>
<script type="text/plain" data-sw-consent="marketing" src="https://example.com/pixel.js"></script>
<iframe data-sw-consent="marketing" data-sw-src="https://www.youtube-nocookie.com/embed/..."></iframe>
```

PHP: inject `Softaware\CookieConsent\Model\ScriptBlocker` (or use the view model
`Softaware\CookieConsent\ViewModel\ScriptBlocker` in templates):

```php
echo $scriptBlocker->block($vendorHtml, 'marketing');          // converts script/iframe/img
<script <?= $scriptBlocker->attributes('analytics') ?>>...</script>
```

When the module is disabled both return the markup unchanged, so your module keeps working without it.
Server-side checks of the cookie are deliberately not offered: pages are full-page cached, so decide in the browser.

## CLI

- `bin/magento softaware:cookie-consent:bump-version` – increase the consent version in every scope where it is set and
  clean config + full-page cache.
- `bin/magento softaware:cookie-consent:log:clean [--days=N]` – delete old log entries (the cron does this daily at 03:17).

## Compatibility

Magento Open Source / Adobe Commerce 2.4.7–2.4.9, PHP 8.2–8.5. Luma/Blank and Hyvä 1.3+ (templates in
`view/frontend/templates/hyva`, Tailwind classes registered through `hyva_config_generate_before`). Checkout on Hyvä
uses the Luma checkout and therefore the Luma banner. Requires `softaware/module-base`.

## Limitations

- The banner is shown to every visitor; regions only change Google's default consent state (no server-side geolocation,
  because pages are cached).
- Scripts added directly in templates or CMS blocks are not blocked automatically – move them to *Third-party Scripts*
  or use the `data-sw-consent` markup. Scripts in *Design > HTML Head / Footer* are blocked only when *Scripts in Design
  Configuration* is set (default: unchanged, so upgrades do not change existing pages).
- Only first-party cookies on the shop's domain can be deleted; cookies of other domains (for example doubleclick.net)
  stay until the vendor removes them. The page reloads after a withdrawal so scripts that already ran stop.
- A blocked script's own CSP needs (for example `connect-src` of a pixel) still have to be whitelisted.
