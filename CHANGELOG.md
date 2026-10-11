# Changelog

## 1.3.4 (2026-10-11)
- Box banner: "Reject all" and "Accept all" side by side with the same width on Luma and Hyvä.
- Preferences hidden: personalization_storage now stays denied; only functionality_storage counts as necessary.
- Default banner message no longer mentions advertising, so it stays true when Marketing is hidden.
- Settings checked on the server: number ranges (expiry up to 730 days) and plain-text button labels and names.
- Requires Softaware Core 1.3.

## 1.3.3 (2026-10-10)
- Admin script form: Save and Save and Continue Edit no longer open a 404 page instead of saving.

## 1.3.2 (2026-10-10)
- Third-party Scripts: their hosts are added to the CSP header on full-page-cached pages too.
- Docs mention the demo scripts; new Third-party Scripts screenshot.

## 1.3.1 (2026-10-09)
- Product listing, FAQ and user guide.

## 1.3.0 (2026-10-07)
- Third-party Scripts grid: keyword search, status badges, introduction and empty state.
- Consent Log grid: Store View filter, introduction, empty state and shorter rows.
- Every setting has a short comment with its default.

## 1.2.0 (2026-10-06)
- Requires Softaware Core instead of the retired Softaware Base; admin menu and ACL under "Softaware".

## 1.1.0 (2026-10-06)
- Scripts pasted into Content > Design > Configuration can wait for a cookie category.
- Tracking cookies of refused categories are deleted (pre-filled for common analytics and ad tools).
- Fixed: with Regions and "Default Outside These Regions" = Denied, visitors outside had no denied default.
- Fixed: images, media and stylesheets in script snippets loaded before consent.
- Consent log endpoint limited to 60 entries per IP hash and hour.

## 1.0.0 (2026-10-06)
- First release: cookie banner (bar or box) and accessible preferences window for Luma and Hyvä.
- Necessary, Preferences, Analytics and Marketing categories plus your own; texts per store view.
- Google Consent Mode v2 defaults and updates; consent-gated third-party scripts managed in the admin.
- Optional consent log with admin grid, export and retention cron; CLI commands.
