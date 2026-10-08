// Tests für public/begin-checkout.js: „begin_checkout“ genau einmal je Kassengang. Ginge das schief,
// zählten GA4 und die Ads-Conversion den Kassenbeginn wieder doppelt, oder gar nicht mehr.
//
// Ohne Abhängigkeiten, nur node:test. Das Skript wird mit einem Ersatz-Fenster ausgewertet; so läuft der
// ausgelieferte Quelltext unverändert.

import { describe, test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';
import vm from 'node:vm';

const __dirname = dirname(fileURLToPath(import.meta.url));
const source = readFileSync(join(__dirname, '..', '..', 'src', 'Resources', 'public', 'begin-checkout.js'), 'utf8');

function memoryStorage() {
    const data = new Map();
    return {
        getItem: (key) => (data.has(key) ? data.get(key) : null),
        setItem: (key, value) => data.set(key, String(value)),
        removeItem: (key) => data.delete(key),
    };
}

function blockedStorage() {
    return {
        getItem: () => { throw new Error('blocked'); },
        setItem: () => { throw new Error('blocked'); },
        removeItem: () => { throw new Error('blocked'); },
    };
}

// Ein Fenster mit dem Datalayer, wie Wbm ihn im Kopf der Seite hinterlässt.
function page(storage, { wbmEvent = false, items = [{ item_id: 'VS12F', quantity: 2 }], value = 5.3 } = {}) {
    const ecommerce = { currency: 'EUR', value, items };
    const dataLayer = [{ event: 'user' }, { ecommerce: null }, wbmEvent ? { event: 'begin_checkout', ecommerce } : { ecommerce }];
    const window = { dataLayer, localStorage: storage };
    vm.runInNewContext(source, { window, globalThis: window });

    return window;
}

const NOW = 1_791_400_000_000;
const sent = (window) => window.dataLayer.filter((entry) => entry && entry.event === 'begin_checkout').length;

describe('ein Kassengang als Gast', () => {
    test('Anmeldung, Eingabefehler, Bestätigung, Neuladen: einmal gezählt', () => {
        const storage = memoryStorage();
        const steps = [['login', 'sent'], ['login', 'already-counted'], ['confirm', 'already-counted'], ['confirm', 'already-counted']];
        let total = 0;
        for (const [step, expected] of steps) {
            const window = page(storage);
            assert.equal(window.RcTrackingBeginCheckout.run(window, step, NOW), expected, step);
            total += sent(window);
        }
        assert.equal(total, 1);
    });

    test('die Warenkorbdaten gehen unverändert mit', () => {
        const window = page(memoryStorage());
        window.RcTrackingBeginCheckout.run(window, 'login', NOW);
        const event = window.dataLayer.at(-1);
        assert.equal(event.event, 'begin_checkout');
        assert.equal(event.ecommerce.value, 5.3);
        assert.equal(event.ecommerce.items[0].item_id, 'VS12F');
        // Aus dem VM-Kontext: Prototyp verschieden, deshalb über JSON verglichen.
        assert.equal(JSON.stringify(window.dataLayer.at(-2)), '{"ecommerce":null}', 'vorher geleert, wie Wbm es tut');
    });
});

describe('wann neu gezählt wird', () => {
    test('ein angemeldeter Kunde beginnt auf der Bestätigungsseite', () => {
        const window = page(memoryStorage());
        assert.equal(window.RcTrackingBeginCheckout.run(window, 'confirm', NOW), 'sent');
    });

    test('ein geänderter Warenkorb ist ein neuer Kassengang', () => {
        const storage = memoryStorage();
        page(storage).RcTrackingBeginCheckout.run(page(storage), 'login', NOW);
        const changed = page(storage, { items: [{ item_id: 'VS12F', quantity: 3 }], value: 7.95 });
        assert.equal(changed.RcTrackingBeginCheckout.run(changed, 'confirm', NOW), 'sent');
    });

    test('nach dem Kauf zählt derselbe Warenkorb wieder', () => {
        const storage = memoryStorage();
        const first = page(storage);
        first.RcTrackingBeginCheckout.run(first, 'confirm', NOW);
        const finish = page(storage);
        assert.equal(finish.RcTrackingBeginCheckout.run(finish, 'finish', NOW), 'cleared');
        const again = page(storage);
        assert.equal(again.RcTrackingBeginCheckout.run(again, 'confirm', NOW), 'sent');
    });

    test('nach einem Tag zählt derselbe Warenkorb wieder', () => {
        const storage = memoryStorage();
        const first = page(storage);
        first.RcTrackingBeginCheckout.run(first, 'login', NOW);
        const nextDay = page(storage);
        assert.equal(nextDay.RcTrackingBeginCheckout.run(nextDay, 'login', NOW + 24 * 60 * 60 * 1000), 'sent');
    });

    test('die Reihenfolge der Artikel ändert den Fingerabdruck nicht', () => {
        const window = page(memoryStorage());
        const { signature } = window.RcTrackingBeginCheckout;
        const a = { value: 3, currency: 'EUR', items: [{ item_id: 'A', quantity: 1 }, { item_id: 'B', quantity: 2 }] };
        const b = { value: 3, currency: 'EUR', items: [{ item_id: 'B', quantity: 2 }, { item_id: 'A', quantity: 1 }] };
        assert.equal(signature(a), signature(b));
    });
});

describe('Übergang und Rückfälle', () => {
    test('sendet Wbm selbst noch, kommt nichts dazu', () => {
        const storage = memoryStorage();
        const login = page(storage, { wbmEvent: true });
        assert.equal(login.RcTrackingBeginCheckout.run(login, 'login', NOW), 'sent-by-wbm');
        assert.equal(sent(login), 1, 'nur das von Wbm');
    });

    test('ohne Speicher nur auf der Bestätigungsseite', () => {
        const login = page(blockedStorage());
        assert.equal(login.RcTrackingBeginCheckout.run(login, 'login', NOW), 'no-storage');
        const confirm = page(blockedStorage());
        assert.equal(confirm.RcTrackingBeginCheckout.run(confirm, 'confirm', NOW), 'sent');
    });

    test('ohne Warenkorbdaten nichts', () => {
        const window = { dataLayer: [{ event: 'user' }], localStorage: memoryStorage() };
        vm.runInNewContext(source, { window, globalThis: window });
        assert.equal(window.RcTrackingBeginCheckout.run(window, 'confirm', NOW), 'no-cart-data');
        assert.equal(sent(window), 0);
    });
});
