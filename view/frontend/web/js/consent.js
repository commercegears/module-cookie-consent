/**
 * Softaware Cookie Consent - banner, preferences window and consent-gated scripts.
 * Vanilla JS (no RequireJS, jQuery or Alpine), shared by Luma and Hyvä.
 *
 * Public API (window.SwConsent), available as a stub from the <head> and completed here:
 *   SwConsent.has('analytics')               -> boolean
 *   SwConsent.whenGranted('marketing', fn)   -> runs fn now or once the category is accepted
 *   SwConsent.onChange(fn)                   -> fn({categories: [...], trigger: 'user'}) after every choice
 *   SwConsent.open()                         -> opens the preferences window
 *   SwConsent.acceptAll() / rejectAll() / save(['necessary', 'analytics'])
 * DOM event: document 'sw-consent:change' (detail = same object as onChange).
 * dataLayer event: 'softaware_consent_update' with sw_consent {category: 'granted'|'denied'}.
 */
(function () {
    'use strict';

    var w = window, d = document;
    var root = d.getElementById('sw-cc');
    var api = w.SwConsent;
    var c = w.swConsentConfig;

    if (!root || !api || !c || api._ready) {
        return;
    }

    var opts = {};
    try {
        opts = JSON.parse(root.getAttribute('data-sw-cc-config') || '{}');
    } catch (e) { /* keep defaults */ }

    var banner = root.querySelector('[data-sw-cc-banner]');
    var bannerBackdrop = root.querySelector('[data-sw-cc-banner-backdrop]');
    var modal = root.querySelector('[data-sw-cc-modal]');
    var dialog = root.querySelector('[data-sw-cc-dialog]');
    var floatBtn = root.querySelector('[data-sw-cc-float]');
    var listeners = [];
    var lastFocus = null;
    var FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), select, textarea, [tabindex]:not([tabindex="-1"])';

    function has(list, code) {
        return list.indexOf(code) !== -1;
    }

    function uuid() {
        if (w.crypto && typeof w.crypto.randomUUID === 'function') {
            return w.crypto.randomUUID();
        }
        var b = new Uint8Array(16);
        (w.crypto || w.msCrypto).getRandomValues(b);
        b[6] = (b[6] & 0x0f) | 0x40;
        b[8] = (b[8] & 0x3f) | 0x80;
        var h = Array.prototype.map.call(b, function (x) { return (x + 0x100).toString(16).slice(1); }).join('');
        return h.slice(0, 8) + '-' + h.slice(8, 12) + '-' + h.slice(12, 16) + '-' + h.slice(16, 20) + '-' + h.slice(20);
    }

    function getCookie(name) {
        var m = d.cookie.match(new RegExp('(?:^|; )' + name.replace(/([.$?*|{}()[\]\\/+^])/g, '\\$1') + '=([^;]*)'));
        return m ? decodeURIComponent(m[1]) : null;
    }

    function setCookie(name, value, maxAge) {
        var parts = [name + '=' + encodeURIComponent(value), 'path=' + (opts.cookiePath || '/'), 'SameSite=Lax'];
        if (maxAge) { parts.push('max-age=' + maxAge); }
        if (opts.cookieDomain) { parts.push('domain=' + opts.cookieDomain); }
        if (opts.secure || location.protocol === 'https:') { parts.push('secure'); }
        d.cookie = parts.join('; ');
    }

    function formKey() {
        var key = getCookie('form_key');
        if (!key) {
            var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789', bytes = new Uint8Array(16);
            w.crypto.getRandomValues(bytes);
            key = Array.prototype.map.call(bytes, function (x) { return chars[x % chars.length]; }).join('');
            setCookie('form_key', key, 0);
        }
        return key;
    }

    function categoryMap(list) {
        var cats = {};
        c.categories.forEach(function (code) { cats[code] = has(list, code) ? 'granted' : 'denied'; });
        return cats;
    }

    /* ---------------------------------------------------------------- consent-gated scripts */

    function allowed(node) {
        return (node.getAttribute('data-sw-consent') || '').split(/[\s,]+/).filter(Boolean).every(function (code) {
            return api.has(code);
        });
    }

    function activateScript(old, done) {
        var s = d.createElement('script'), i, a, external = old.hasAttribute('src');
        for (i = 0; i < old.attributes.length; i++) {
            a = old.attributes[i];
            if (a.name !== 'type' && a.name !== 'data-sw-consent' && a.name !== 'data-sw-type' && a.name !== 'nonce') {
                s.setAttribute(a.name, a.value);
            }
        }
        if (old.getAttribute('data-sw-type')) { s.type = old.getAttribute('data-sw-type'); }
        if (old.nonce) { s.nonce = old.nonce; }
        s.setAttribute('data-sw-consent-activated', old.getAttribute('data-sw-consent'));
        if (external) {
            if (!old.hasAttribute('async')) { s.async = false; }
            s.onload = s.onerror = function () { done(); };
        } else {
            s.text = old.text;
        }
        old.parentNode.replaceChild(s, old);
        if (!external || old.hasAttribute('async')) { done(); }
    }

    function activateBlocked() {
        var nodes = Array.prototype.slice.call(d.querySelectorAll('[data-sw-consent]')).filter(function (n) {
            return !n.hasAttribute('data-sw-consent-done') && allowed(n);
        });
        nodes.forEach(function (n) { n.setAttribute('data-sw-consent-done', '1'); });
        (function next() {
            var n = nodes.shift();
            if (!n) { return; }
            if (n.tagName === 'SCRIPT') {
                activateScript(n, next);
                return;
            }
            if (n.hasAttribute('data-sw-src')) {
                n.setAttribute('src', n.getAttribute('data-sw-src'));
                n.removeAttribute('data-sw-src');
            }
            next();
        })();
    }

    function flushQueue() {
        var pending = api._queue.splice(0, api._queue.length);
        pending.forEach(function (entry) {
            if (api.has(entry[0])) {
                try { entry[1](); } catch (e) { w.console && console.error(e); }
            } else {
                api._queue.push(entry);
            }
        });
    }

    /* ---------------------------------------------------------------- banner and window */

    function showBanner(focusTarget) {
        if (!banner) { return; }
        banner.hidden = false;
        if (bannerBackdrop) { bannerBackdrop.hidden = false; }
        if (floatBtn) { floatBtn.hidden = true; }
        var target = focusTarget && banner.contains(focusTarget) ? focusTarget : banner;
        try { target.focus({ preventScroll: true }); } catch (e) { target.focus(); }
    }

    function hideBanner() {
        if (banner) { banner.hidden = true; }
        if (bannerBackdrop) { bannerBackdrop.hidden = true; }
    }

    function syncSwitches() {
        var granted = api.state ? api.state.c : c.required;
        root.querySelectorAll('[data-sw-cc-category]').forEach(function (input) {
            var code = input.getAttribute('data-sw-cc-category');
            input.checked = has(c.required, code) || has(granted, code);
        });
    }

    function openModal(opener) {
        if (!modal) { return; }
        lastFocus = opener || d.activeElement;
        syncSwitches();
        modal.hidden = false;
        d.body.classList.add('sw-cc-modal-open');
        d.documentElement.style.overflow = 'hidden';
        var first = dialog.querySelector('[data-sw-cc-category]:not([disabled])') || dialog;
        try { dialog.focus({ preventScroll: true }); } catch (e) { dialog.focus(); }
        if (first !== dialog) { first.focus(); }
    }

    function closeModal() {
        if (!modal || modal.hidden) { return; }
        modal.hidden = true;
        d.body.classList.remove('sw-cc-modal-open');
        d.documentElement.style.overflow = '';
        if (!api.state) {
            showBanner(lastFocus);
        } else if (lastFocus && lastFocus.focus && d.body.contains(lastFocus)) {
            lastFocus.focus();
        }
    }

    function trapFocus(e) {
        if (!modal || modal.hidden) { return; }
        if (e.key === 'Escape') {
            e.preventDefault();
            closeModal();
            return;
        }
        if (e.key !== 'Tab') { return; }
        var items = Array.prototype.filter.call(dialog.querySelectorAll(FOCUSABLE), function (el) {
            return el.offsetParent !== null;
        });
        if (!items.length) { return; }
        var first = items[0], last = items[items.length - 1];
        if (e.shiftKey && (d.activeElement === first || d.activeElement === dialog)) {
            e.preventDefault();
            last.focus();
        } else if (!e.shiftKey && d.activeElement === last) {
            e.preventDefault();
            first.focus();
        }
    }

    /* ---------------------------------------------------------------- saving */

    function log(state, action) {
        if (!opts.logUrl) { return; }
        var data = new FormData();
        data.append('form_key', formKey());
        data.append('consent_id', state.id);
        data.append('action', action);
        data.append('categories', state.c.join(','));
        data.append('version', String(state.v));
        if (navigator.sendBeacon && navigator.sendBeacon(opts.logUrl, data)) {
            return;
        }
        if (w.fetch) {
            fetch(opts.logUrl, { method: 'POST', body: data, credentials: 'same-origin', keepalive: true })
                .catch(function () { /* logging must never break the page */ });
        }
    }

    function save(categories, action) {
        var previous = api.state;
        var list = c.categories.filter(function (code) {
            return has(c.required, code) || has(categories, code);
        });
        var state = {
            v: c.version,
            id: previous && previous.id ? previous.id : uuid(),
            ts: Math.floor(Date.now() / 1000),
            c: list
        };
        api.state = state;
        setCookie(c.cookie, JSON.stringify(state), c.expiryDays * 86400);

        if (c.consentMode && typeof w.gtag === 'function') {
            w.gtag('consent', 'update', api.consentTypes(list));
        }
        var detail = { categories: list.slice(), trigger: 'user', action: action || 'custom', version: state.v };
        (w.dataLayer = w.dataLayer || []).push({
            event: 'softaware_consent_update',
            sw_consent_trigger: 'user',
            sw_consent: categoryMap(list),
            sw_consent_categories: list.join(','),
            sw_consent_version: state.v
        });

        hideBanner();
        if (modal && !modal.hidden) {
            modal.hidden = true;
            d.body.classList.remove('sw-cc-modal-open');
        d.documentElement.style.overflow = '';
        }
        if (floatBtn) { floatBtn.hidden = false; }

        log(state, action || 'custom');
        activateBlocked();
        flushQueue();
        listeners.forEach(function (fn) {
            try { fn(detail); } catch (e) { w.console && console.error(e); }
        });
        try {
            d.dispatchEvent(new CustomEvent('sw-consent:change', { detail: detail }));
        } catch (e) { /* old browsers */ }

        // Scripts that already ran cannot be unloaded: reload when a category is withdrawn.
        var revoked = previous && previous.c.some(function (code) { return !has(list, code); });
        if (revoked) {
            setTimeout(function () { location.reload(); }, 150);
        } else if (lastFocus && lastFocus.focus && d.body.contains(lastFocus) && lastFocus !== d.body) {
            lastFocus.focus();
        }
    }

    function chosenCategories() {
        var list = c.required.slice();
        root.querySelectorAll('[data-sw-cc-category]').forEach(function (input) {
            if (input.checked && !has(list, input.getAttribute('data-sw-cc-category'))) {
                list.push(input.getAttribute('data-sw-cc-category'));
            }
        });
        return list;
    }

    /* ---------------------------------------------------------------- wiring */

    api.acceptAll = function () { save(c.categories.slice(), 'accept_all'); };
    api.rejectAll = function () { save(c.required.slice(), 'reject_all'); };
    api.save = function (list) { save(list || [], 'custom'); };
    api.open = function () { openModal(d.activeElement); };
    api.onChange = function (fn) { if (typeof fn === 'function') { listeners.push(fn); } };
    api.get = function () { return api.state ? { categories: api.state.c.slice(), version: api.state.v, id: api.state.id, timestamp: api.state.ts } : null; };
    api._ready = true;

    root.addEventListener('click', function (e) {
        var target = e.target.closest('[data-sw-cc-action]');
        if (!target || !root.contains(target)) { return; }
        var action = target.getAttribute('data-sw-cc-action');
        if (action === 'accept') {
            api.acceptAll();
        } else if (action === 'reject') {
            api.rejectAll();
        } else if (action === 'save') {
            save(chosenCategories(), 'custom');
        } else if (action === 'settings' || action === 'open') {
            openModal(target);
        } else if (action === 'close') {
            closeModal();
        }
    });

    d.addEventListener('click', function (e) {
        var link = e.target.closest('[data-sw-cc-open], a[href$="#cookie-settings"]');
        if (link) {
            e.preventDefault();
            openModal(link);
        }
    });

    d.addEventListener('keydown', trapFocus);

    if (api.state) {
        if (floatBtn) { floatBtn.hidden = false; }
    } else {
        showBanner();
    }
    activateBlocked();
    flushQueue();

    if (location.hash === '#cookie-settings') {
        openModal(null);
    }
})();
