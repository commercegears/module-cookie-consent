# Cookie Consent for Magento 2: FAQ

**Is Cookie Consent free?**
Yes. The module is free to download from commercegears.com and is installed with Composer like our other
modules. It needs `commercegears/module-core`, which Composer installs automatically.

**Will this module make my shop legally compliant?**
No module can promise that on its own. Cookie Consent gives you the tools: a banner with "Accept all", "Reject all"
and per-category choices, scripts that wait for consent, Google Consent Mode v2 signals and an optional log of the
choices. Whether your set-up meets the rules that apply to you depends on which tags you run, how you categorise them
and what your cookie policy says. Ask your legal adviser if you are unsure.

**Which scripts are held back until the visitor agrees?**
Snippets you add under **CommerceGears > Cookie Consent > Third-party Scripts**, any markup with the `data-sw-consent`
attribute, and (when you choose a category under **General > Scripts in Design Configuration**) the scripts in
**Content > Design > Configuration** (HTML Head and Footer). Scripts hard-coded in templates or CMS blocks are not
changed automatically: move them to Third-party Scripts or add the `data-sw-consent` markup. The settings page and the
Third-party Scripts grid list every Design Configuration script that still loads without consent.

**Does it work with Google Tag Manager and Google Consent Mode v2?**
Yes. With Consent Mode switched on, `gtag('consent', 'default', ...)` is printed directly after the page title,
before any CSS, JavaScript or tag manager. After a choice, and on every later page for returning visitors,
`gtag('consent', 'update', ...)` is sent. A dataLayer event `commercegears_consent_update` with the accepted categories
is pushed as well, so you can use it as a GTM trigger.

**Does it work with full-page cache?**
Yes. The banner and the scripts contain only store configuration, and the visitor's choice is read in the browser
from the `sw_consent` cookie. Pages stay cacheable.

**Does it work with Hyvä?**
Yes. Hyvä templates (Tailwind, Hyvä 1.3+) are included and picked automatically; Luma, Blank and their child themes
have their own templates. On Hyvä the checkout uses the Luma checkout, so the Luma banner appears there.

**How do visitors change their mind?**
Through the "Cookie settings" link in the footer, a floating button, the CMS widget "CommerceGears Cookie Settings Link",
or any link to `#cookie-settings`. When a visitor withdraws a category, matching first-party cookies are deleted and
the page reloads, so scripts that already ran stop.

**How do I ask every visitor again, for example after adding a new tracking tool?**
Increase **General > Consent Version**, or run `bin/magento commercegears:cookie-consent:bump-version`. Visitors whose
stored choice has an older version see the banner again. Choices also expire after **Consent Expiry (Days)**
(default 180).

**What does the consent log store?**
When **Consent Log > Record Consent Choices** is on: a random consent ID created in the browser, the store view, the
choice (accepted all, rejected all or a custom choice), the accepted categories, the consent version, the time and a
keyed hash (HMAC-SHA256) of the IP address, optionally truncated before hashing. No names, email addresses or raw IP
addresses are stored. Old entries are deleted by a daily cron job after the number of days you set.

**Are cookies deleted when a visitor refuses a category?**
First-party cookies listed under **Cookie Categories > Cookies to Delete When Refused** are deleted in the browser
(the list comes pre-filled for Google Analytics, Clarity, Hotjar, Meta, Google Ads, Microsoft Ads, TikTok and
Pinterest). Cookies set on other domains cannot be deleted by your shop. Shop cookies such as the session, form key
and basket are never deleted.

**Can I show the banner only to visitors in some countries?**
No. The banner is shown to every visitor, because pages are cached and there is no server-side geolocation.
**Regions Requiring Consent** only changes Google's default consent state for the listed regions.

**What happens to Magento's own cookie notice?**
While the module is enabled, Magento's "Cookie Restriction Mode" notice is switched off (setting **Replace Magento
Cookie Restriction Notice**, default Yes), so visitors see one banner.

**Which Magento and PHP versions are supported?**
Magento Open Source and Adobe Commerce 2.4.7 to 2.4.9, PHP 8.2 to 8.5.
