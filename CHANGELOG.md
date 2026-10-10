# Changelog

## 1.3.2 (2026-10-10)

### Fixed

- Third-party Scripts: on pages served from Magento's built-in full-page cache the CSP header did not contain the
  scripts' remote hosts (Magento rebuilds the header on a cache hit, and the dynamic CSP collector is only filled
  while a page is rendered). After consent the browser reported the scripts (report-only) or, with the storefront CSP
  in restrict mode, refused them. A CSP collector now adds the hosts of the store view's active scripts on every
  storefront response, cached or not.

### Changed

- Docs: the user guide mentions the demo scripts on demo.softawarecommerce.com, and the Third-party Scripts
  screenshot shows them.

## 1.3.1 (2026-10-09)

### Added

- Product listing, FAQ and user guide (docs/)

## 1.3.0 (2026-10-07)

### Added

- Third-party Scripts grid: keyword search (script name).
- Consent Log grid: Store View filter.

### Changed

- Third-party Scripts: page title matches the menu ("Third-party Scripts"), one-sentence introduction, and an empty
  state with "Add Your First Script" instead of an empty table. Status is shown as an Active / Inactive badge.
  A notice appears when Cookie Consent is switched off.
- Consent Log: introduction, an empty state, a notice when Record Consent Choices is off, and the store view on one
  line instead of three (rows are a third of the height).
- Script form: notes for Position, Store Views and Sort Order.
- Settings: every field has a short comment with its default; long comments shortened; "Keep Entries For (Days)" is
  hidden while the consent log is off; "Wait for Update (ms)" accepts only 0 or more.
- The design-configuration warning uses a module CSS file instead of inline styles and a shorter text on the
  settings page.

## 1.2.0 (2026-10-06)

### Changed

- Requires `softaware/module-core` instead of `softaware/module-base`. The admin menu and ACL now sit under `Softaware_Core::core` ("Softaware"); roles that had access keep it (migrated by module-core). After updating all SoftAware modules, `softaware/module-base` can be removed.

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
