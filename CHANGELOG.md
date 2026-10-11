# Changelog

## 2.0.0 (2026-10-11)
- Improvement: renamed to CommerceGears (new Composer package, admin menu and settings paths)

## 1.3.4 (2026-10-11)
- Fix: with Preferences hidden, the personalisation consent now stays denied.
- Fix: the default banner message no longer mentions advertising, so it stays true when Marketing is hidden.
- Improvement: box banner shows "Reject all" and "Accept all" side by side at the same width on Luma and Hyvä.
- Improvement: settings are checked when saved: number ranges (expiry up to 730 days) and plain-text labels.
- Improvement: requires CommerceGears Core 1.3.

## 1.3.3 (2026-10-10)
- Fix: Save and Save and Continue Edit on the script form no longer open a 404 page instead of saving.

## 1.3.2 (2026-10-10)
- Fix: third-party script hosts are now allowed by the security policy on full-page-cached pages too.
- Improvement: docs mention the demo scripts, with a new Third-party Scripts screenshot.

## 1.3.1 (2026-10-09)
- New: product listing, FAQ and user guide.

## 1.3.0 (2026-10-07)
- New: keyword search on the Third-party Scripts grid and a Store View filter on the Consent Log grid.
- Improvement: introductions, empty states, status badges and shorter rows on both grids.
- Improvement: every setting has a short comment with its default.

## 1.2.0 (2026-10-06)
- Improvement: requires CommerceGears Core instead of CommerceGears Base; menu and permissions move under "CommerceGears".

## 1.1.0 (2026-10-06)
- New: scripts pasted into Content > Design > Configuration can wait for a cookie category.
- New: tracking cookies of refused categories are deleted (pre-filled for common analytics and ad tools).
- Fix: visitors outside the listed regions now get a denied default when that is set.
- Fix: images, media and stylesheets in script snippets no longer load before consent.
- Improvement: the consent log accepts at most 60 entries per IP address and hour.

## 1.0.0 (2026-10-06)
- New: first release with a cookie banner (bar or box) and accessible preferences window for Luma and Hyvä.
- New: Necessary, Preferences, Analytics and Marketing categories plus your own; texts per store view.
- New: Google Consent Mode v2 defaults and updates; consent-gated third-party scripts managed in the admin.
- New: optional consent log with admin grid, export and retention clean-up; command line tools.
