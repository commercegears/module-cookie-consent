# Changelog

## 1.0.0

- Cookie banner (bar or box, five positions) and accessible preferences window (keyboard, focus trap, ARIA).
- Categories: necessary (always on), preferences, analytics, marketing, plus custom categories; texts per store view.
- Accept all / reject all / save choices; "Cookie settings" footer link, floating button, CMS widget and `#cookie-settings` links.
- Consent stored in the first-party cookie `sw_consent` with version and timestamp; version bump and expiry re-ask visitors.
- Google Consent Mode v2: defaults at the top of `<head>` (regions, wait_for_update, ads_data_redaction, url_passthrough),
  `consent update` on choice and on every page for returning visitors; dataLayer event `softaware_consent_update`.
- Consent-gated third-party scripts managed in the admin (per category, position and store view) and a PHP/JS API for developers.
- Optional consent log (random id, choice, version, keyed IP hash) with admin grid, CSV/XML export and retention cron.
- Replaces Magento's cookie restriction notice; Luma and Hyvä templates; full-page-cache safe.
- CLI: `softaware:cookie-consent:bump-version`, `softaware:cookie-consent:log:clean`.
