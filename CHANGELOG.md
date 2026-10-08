# Changelog

Alle wesentlichen Änderungen an diesem Projekt werden hier dokumentiert.
Format basiert auf [Keep a Changelog](https://keepachangelog.com/de/1.0.0/).

## [1.5.0] - 2026-10-08 — Kassenbeginn genau einmal je Kassengang

> **Deployment:** `php bin/console plugin:install --activate RcTracking` (bzw. `plugin:update`),
> `php bin/console assets:install`, `php bin/console cache:clear`. Danach in Wbm an „Checkout Register Page“
> und „Confirm Page“ die Eigenschaft „event“ entfernen und den Schalter einschalten.

### Neu

- **„begin_checkout genau einmal je Kassengang“** (Schalter, ab Werk aus). WbmTagManagerAnalytics meldete
  den Kassenbeginn an Anmelde- und Bestätigungsseite, also für jeden Gast zweimal und nach jedem
  Eingabefehler erneut. Ein Skript am Seitenende nimmt die Warenkorbdaten von Wbm und sendet das Ereignis
  einmal je Warenkorb (Fingerabdruck einen Tag im `localStorage`, auf der Abschlussseite gelöscht). Schreibt
  Wbm selbst noch `begin_checkout`, sendet das Skript nichts dazu. Gemessen an einer Kopie des Shops: Gast mit
  Anmeldung und Bestätigung 3 → 1 Sendung an GA4, mit Artikeln, Wert und Währung.

## [1.4.0] - 2026-07-21 — Consent-Prüfung korrigiert (Marketing lädt nur bei echter Einwilligung)

> **Deployment:** `php bin/console plugin:update RcTracking && php bin/console cache:clear`.

### Behoben

- **Tracking lud bei jeder Consentmanager-Interaktion — auch bei „Alles ablehnen".** Die Einwilligung wurde an
  der bloßen Präsenz einer Consent-Zeichenkette festgemacht. Diese wird jedoch nach *jeder* Entscheidung gesetzt,
  auch bei Ablehnung — sie kodiert die getroffene Wahl, sie ist keine Zustimmung. Google Tag Manager, Microsoft
  UET und der benutzerdefinierte Code feuerten dadurch auch ohne Marketing-Einwilligung. Die Einwilligung wird
  jetzt ausschließlich am tatsächlichen Marketing-Zweck geprüft.

### Geändert

- **Anbindung an beide Consentmanager-Betriebsarten.** Unterstützt jetzt sowohl die native consentmanager.net-
  Schnittstelle (`purposeConsents` mit Purpose-Code) als auch den IAB-TCF-Standard (`__tcfapi`,
  `purpose.consents` mit numerischer Purpose-ID). Zuvor wurde eine Schnittstellenform erwartet, die der native
  Modus gar nicht liefert — dort lud das Tracking überhaupt nicht.
- **Ohne konfigurierte Marketing-Purpose-ID wird nichts geladen (Default-Deny).** Das Feld nimmt je nach
  Consentmanager den Purpose-Code (z. B. „c2") oder die numerische Purpose-ID (z. B. „5") entgegen.

### Sicherheit

- Kein Marketing-Tracking mehr ohne ausdrückliche, zweckbezogene Einwilligung des Nutzers (DSGVO/TTDSG).

## [1.3.0] - 2026-07-14 — GTM-`<noscript>`-Fallback entfernt (DSGVO)

> **Deployment:** `php bin/console plugin:update RcTracking && php bin/console cache:clear`.

### Entfernt

- **`layout/body.html.twig` mit dem GTM-`<noscript>`-iframe — ersatzlos.**

  Zwei Befunde führten dazu:

  1. **Es hat nie funktioniert.** Das Template erweiterte `@Storefront/storefront/layout/body.html.twig` — ein Template, das es in Shopware nicht gibt (den `<body>` definiert `base.html.twig`). Twig ignoriert das stillschweigend; das Snippet rendert nie.
  2. **Es hätte nicht funktionieren dürfen.** Ein `<noscript>`-iframe lädt bedingungslos — ohne JavaScript ist keine Einwilligungsprüfung möglich. Und ein Nutzer ohne JavaScript kann den Consentmanager nicht bedienen, denn der ist selbst JavaScript: **Er kann niemals einwilligen.** Das Fallback hätte ausgerechnet diese Nutzer getrackt. Das widerspricht DSGVO und TTDSG — und dem erklärten Prinzip dieses Plugins.

  Eine serverseitige Consent-Prüfung hilft nicht: Wer kein JavaScript hat, kann kein Consent-Cookie besitzen. Wer JavaScript hat, führt `<noscript>` nie aus. Das Snippet kann in **keinem** Fall legitim feuern.

  Der Preis: Nutzer ohne JavaScript tauchen in GTM nicht auf. Das ist der Preis eines Consent-Managements.

### Behoben

- **README widersprach dem Code.** Er behauptete: „Kein Consentmanager vorhanden? Dann lädt das Tracking direkt." Der Code tut das Gegenteil — er verweigert das Tracking und protokolliert eine Warnung. Der Code hat recht, der README ist korrigiert.

### Hinzugefügt

- **Regressions-Sperre** `testKeinGtmNoscriptFallbackImPlugin`: scannt alle Twig-Templates und schlägt an, sobald irgendwo wieder ein GTM-`<noscript>` auftaucht.

### Hinweis

Die vier bisherigen Body-Template-Tests sind entfallen. Einer davon prüfte **grün**, dass das Template auf eine nicht existierende Datei zeigt. Ein Test kann einen Bug auch zementieren, statt ihn zu finden.

## [1.2.0] – 2026-05-11

> **Deployment:** `php bin/console cache:clear`. Keine Datenbank-Migration, kein Storefront-Build (Test- und Doku-Änderungen).

### Hinzugefügt
- **Test-Suite massiv erweitert:** 26 PHPUnit-Tests (vorher 1) gegen die kritischen Vertrags-Strings von `meta.html.twig` und `body.html.twig` (`TwigTemplateContractTest`), gegen die Pflicht-Felder und i18n-Vollständigkeit von `config.xml` (`ConfigXmlContractTest`) und gegen die Plugin-Bootstrap-Eigenschaften (`PluginBootstrapTest`). Drift bei Sanitizer-Regex (`'</script' → '<\\/script'`), Consent-Pfaden (`tcString` primär, `purposeConsents` sekundär), Escape-Filtern (`|e('js')`, `|e('html_attr')`), Doppellade-Guard (`trackingLoaded`) und CMP-Refusal (`window.__cmp === 'undefined'`) wird ab sofort von der CI rot gemeldet.
- `composer.json`: explizites `php: >=8.2`-Constraint (vorher nur implizit über Shopware-Core-Constraint — Diskrepanz zur CI-Matrix auf PHP 8.3).

### Geändert

## [1.1.1] – 2026-04-22

> **Deployment:** `php bin/console cache:clear` (Tooling- und Test-Stabilisierung, keine Verhaltensänderung).

### Geändert


## [1.1.0] – 2026-03-24

> **Deployment:** `php bin/console cache:clear`

### Geändert
- Consentmanager-API auf bewährtes Format `addEventListener(2, ...)` umgestellt
- Consent-Callback prüft jetzt `success`-Flag vor Verarbeitung

### Hinzugefügt
- Config-Feld „Consentmanager Marketing-Purpose-ID": Tracking lädt nur wenn konfigurierten Zweck erlaubt
- Unterstützung für `consentapplied`-Event (wiederkehrende Besucher mit gespeichertem Consent)

## [1.0.0] – 2026-03-24

> **Deployment:** `php bin/console cache:clear` (Erstinstallation)

### Hinzugefügt
- Google Tag Manager Integration (GTM-ID konfigurierbar)
- Microsoft UET Integration (aktivierbar/deaktivierbar, Tag-ID konfigurierbar)
- Benutzerdefinierter Tracking-Code (Freitextfeld für Meta Pixel, TikTok u. a.)
- DSGVO-konforme Consent-Integration via Consentmanager (`window.__cmp`)
- Consent-Handling für Erstbesucher (`useractioncomplete`) und Wiederkehrende (`consentapplied`)
- Fallback: Direktes Laden wenn kein Consentmanager vorhanden
- GTM `<noscript>`-Fallback im `<body>` für NoScript-Umgebungen
- Admin-Konfiguration mit Hilfstext und Sicherheitshinweisen
- Shopware System Config pro Sales Channel überschreibbar
