/**
 * Sendet `begin_checkout` genau einmal je Kassengang.
 *
 * WbmTagManagerAnalytics schreibt die Warenkorbdaten (`ecommerce`) in den Kopf von Anmelde-, Bestätigungs-
 * und Abschlussseite. An Anmelde- und Bestätigungsseite gehängt, feuert sein eigenes `begin_checkout` für
 * jeden Gast zweimal, und noch einmal, wann immer die Anmeldeseite nach einem Eingabefehler neu erscheint.
 * Dieses Skript nimmt die Warenkorbdaten, die Wbm schon geschrieben hat, und sendet das Ereignis nur, wenn
 * dieser Warenkorb noch nicht gezählt ist: Der Fingerabdruck (Artikel × Menge, Wert, Währung) bleibt einen
 * Tag im `localStorage` und wird auf der Abschlussseite gelöscht.
 *
 * Schreibt Wbm auf der Seite selbst noch `begin_checkout`, sendet das Skript nichts und merkt sich nur den
 * Fingerabdruck. Der Schalter lässt sich deshalb vor oder nach der Änderung an den Wbm-Modulen einschalten.
 * Ohne `localStorage` (gesperrt, privates Fenster) lässt sich nichts merken; dann sendet das Skript nur auf
 * der Bestätigungsseite, die jeder Käufer genau einmal erreicht.
 */
(function (root) {
    'use strict';

    var STORAGE_KEY = 'rcTrackingBeginCheckout';
    // Ein Tag: Wer am nächsten Tag mit demselben Warenkorb wiederkommt, beginnt einen neuen Kassengang.
    var MAX_AGE_MS = 24 * 60 * 60 * 1000;

    function lastEcommerce(dataLayer) {
        for (var i = dataLayer.length - 1; i >= 0; i--) {
            var entry = dataLayer[i];
            if (entry && entry.ecommerce && Array.isArray(entry.ecommerce.items) && entry.ecommerce.items.length > 0) {
                return entry.ecommerce;
            }
        }

        return null;
    }

    function hasOwnBeginCheckout(dataLayer) {
        for (var i = 0; i < dataLayer.length; i++) {
            if (dataLayer[i] && dataLayer[i].event === 'begin_checkout') {
                return true;
            }
        }

        return false;
    }

    // Sortiert, weil Wbm die Reihenfolge der Artikel zwischen Anmelde- und Bestätigungsseite nicht zusagt.
    function signature(ecommerce) {
        var items = ecommerce.items.map(function (item) {
            return String(item.item_id || item.item_name || '') + ':' + String(item.quantity || 1);
        }).sort();

        return items.join('|') + '#' + String(ecommerce.value || '') + String(ecommerce.currency || '');
    }

    function openStorage(scope) {
        try {
            var storage = scope.localStorage;
            // Safari im privaten Fenster liefert ein Speicherobjekt, das beim Schreiben wirft.
            var probe = STORAGE_KEY + '-probe';
            storage.setItem(probe, '1');
            storage.removeItem(probe);

            return storage;
        } catch (e) {
            return null;
        }
    }

    function counted(storage, fingerprint, now) {
        try {
            var seen = JSON.parse(storage.getItem(STORAGE_KEY) || 'null');

            return seen !== null && seen.signature === fingerprint && now - seen.at < MAX_AGE_MS;
        } catch (e) {
            return false;
        }
    }

    function remember(storage, fingerprint, now) {
        try {
            storage.setItem(STORAGE_KEY, JSON.stringify({ signature: fingerprint, at: now }));
        } catch (e) {
            // Voller oder gesperrter Speicher: Die nächste Seite zählt dann womöglich erneut, wie bisher.
        }
    }

    /**
     * @param {object} scope  das Fenster
     * @param {string} step   'login', 'confirm' oder 'finish'
     * @param {number} now    Millisekunden seit 1970
     * @returns {string} was geschah, für Tests und Fehlersuche
     */
    function run(scope, step, now) {
        var storage = openStorage(scope);

        if (step === 'finish') {
            if (storage !== null) {
                storage.removeItem(STORAGE_KEY);
            }

            return 'cleared';
        }

        var dataLayer = scope.dataLayer = scope.dataLayer || [];
        var ecommerce = lastEcommerce(dataLayer);
        if (ecommerce === null) {
            return 'no-cart-data';
        }

        var fingerprint = signature(ecommerce);

        if (hasOwnBeginCheckout(dataLayer)) {
            if (storage !== null) {
                remember(storage, fingerprint, now);
            }

            return 'sent-by-wbm';
        }

        if (storage === null) {
            if (step !== 'confirm') {
                return 'no-storage';
            }
        } else if (counted(storage, fingerprint, now)) {
            return 'already-counted';
        } else {
            remember(storage, fingerprint, now);
        }

        // Erst leeren, wie Wbm es tut: Sonst mischt der Tag Manager Felder des vorigen Objekts hinein.
        dataLayer.push({ ecommerce: null });
        dataLayer.push({ event: 'begin_checkout', ecommerce: JSON.parse(JSON.stringify(ecommerce)) });

        return 'sent';
    }

    root.RcTrackingBeginCheckout = { run: run, signature: signature };

    var script = root.document && root.document.currentScript;
    if (script && script.dataset && script.dataset.rcTrackingStep) {
        run(root, script.dataset.rcTrackingStep, Date.now());
    }
}(typeof window !== 'undefined' ? window : globalThis));
