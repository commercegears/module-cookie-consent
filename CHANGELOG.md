# Changelog

## 1.1.0 (2026-10-06)

### Added
- *General > Scripts in Design Configuration*: scripts, iframes and third-party images/stylesheets pasted into
  Content > Design > Configuration (HTML Head > Scripts and Style Sheets, Footer > Miscellaneous HTML) can wait for a
  cookie category. Default: unchanged. The settings page and the Third-party Scripts grid list every script there that
  loads without consent.
- *Cookie Categories > Cookies to Delete When Refused*: first-party tracking cookies (pre-filled for GA, Clarity,
  Hotjar, Meta, Google Ads, Microsoft Ads, TikTok, Pinterest) are deleted when a category is refused or withdrawn.
- `data-sw-consent="necessary"` on a single tag keeps it running; `data-sw-consent="<category>"` overrides the
  snippet's category for that tag.

### Fixed
- Consent Mode v2: with Regions set and "Default Outside These Regions" = Denied, no default was sent for visitors
  outside the regions, so Google tags treated consent as granted there. A global denied default is now sent.
- A normal `<script>` that carried a `data-sw-consent` attribute was run a second time by consent.js.
- Third-party Scripts: an `<img srcset>`, `<source>`, `<video>`/`<audio>`, `<embed>` or external stylesheet in a
  snippet loaded before consent; they are now gated too. Snippets with an unclosed `<script>` tag are refused.

### Security
- Consent log endpoint: at most 60 entries per (truncated) IP hash and hour (new index on ip_hash, created_at),
  and the logged consent version is capped at the current version.
- ACL title of "Manage Third-party Scripts" now says that it allows adding JavaScript to the storefront.

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
