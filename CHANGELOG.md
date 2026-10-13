# Changelog

Alle wesentlichen Änderungen an diesem Projekt werden hier dokumentiert.
Format basiert auf [Keep a Changelog](https://keepachangelog.com/de/1.0.0/).

## [1.5.0] - 2026-10-08: Kassenbeginn genau einmal je Kassengang

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

## [1.4.0] - 2026-07-21: Consent-Prüfung korrigiert (Marketing lädt nur bei echter Einwilligung)

> **Deployment:** `php bin/console plugin:update RcTracking && php bin/console cache:clear`.

### Behoben

- **Das Tracking lud bei jeder Interaktion mit dem Consentmanager, auch bei „Alles ablehnen".** Als Einwilligung
  galt schon, dass überhaupt eine Consent-Zeichenkette vorhanden war. Gesetzt wird sie aber nach *jeder*
  Entscheidung, auch nach einer Ablehnung; sie kodiert die getroffene Wahl und ist keine Zustimmung. Google Tag
  Manager, Microsoft UET und der benutzerdefinierte Code feuerten dadurch auch ohne Marketing-Einwilligung. Geprüft
  wird jetzt ausschließlich der tatsächliche Marketing-Zweck.

### Geändert

- **Anbindung an beide Betriebsarten des Consentmanagers.** Unterstützt wird jetzt die native Schnittstelle von
  consentmanager.net (`purposeConsents` mit Purpose-Code) ebenso wie der IAB-TCF-Standard (`__tcfapi`,
  `purpose.consents` mit numerischer Purpose-ID). Bisher erwartete das Plugin eine Schnittstellenform, die der
  native Modus gar nicht liefert, und dort lud das Tracking überhaupt nicht.
- **Ohne konfigurierte Marketing-Purpose-ID wird nichts geladen (Default-Deny).** Das Feld nimmt je nach
  Consentmanager den Purpose-Code (z. B. „c2") oder die numerische Purpose-ID (z. B. „5") entgegen.

### Sicherheit

- Kein Marketing-Tracking mehr ohne ausdrückliche, zweckbezogene Einwilligung des Nutzers (DSGVO/TTDSG).

## [1.3.0] - 2026-07-14: GTM-`<noscript>`-Fallback entfernt (DSGVO)

> **Deployment:** `php bin/console plugin:update RcTracking && php bin/console cache:clear`.

### Entfernt

- **`layout/body.html.twig` mit dem GTM-`<noscript>`-iframe, ersatzlos.**

  Zwei Befunde führten dazu:

  1. **Es hat nie funktioniert.** Das Template erweiterte `@Storefront/storefront/layout/body.html.twig`, ein Template, das es in Shopware nicht gibt (den `<body>` definiert `base.html.twig`). Twig ignoriert das stillschweigend, und das Snippet rendert nie.
  2. **Es hätte nicht funktionieren dürfen.** Ein `<noscript>`-iframe lädt bedingungslos, denn ohne JavaScript lässt sich keine Einwilligung prüfen. Und wer kein JavaScript hat, kann den Consentmanager nicht bedienen, weil der selbst JavaScript ist: **Er kann niemals einwilligen.** Das Fallback hätte ausgerechnet diese Nutzer getrackt. Das widerspricht DSGVO und TTDSG und ebenso dem erklärten Prinzip dieses Plugins.

  Eine serverseitige Consent-Prüfung hilft nicht: Wer kein JavaScript hat, kann kein Consent-Cookie besitzen. Wer JavaScript hat, führt `<noscript>` nie aus. Das Snippet kann in **keinem** Fall legitim feuern.

  Der Preis: Nutzer ohne JavaScript tauchen in GTM nicht auf. Das ist der Preis eines Consent-Managements.

### Behoben

- **Das README widersprach dem Code.** Dort stand: „Kein Consentmanager vorhanden? Dann lädt das Tracking direkt." Der Code tut das Gegenteil, er verweigert das Tracking und protokolliert eine Warnung. Der Code hat recht, das README ist korrigiert.

### Hinzugefügt

- **Regressions-Sperre** `testKeinGtmNoscriptFallbackImPlugin`: Sie scannt alle Twig-Templates und schlägt an, sobald irgendwo wieder ein GTM-`<noscript>` auftaucht.

### Hinweis

Die vier bisherigen Tests zum Body-Template sind entfallen. Einer davon prüfte **grün**, dass das Template auf eine nicht existierende Datei zeigt. Ein Test kann einen Bug eben auch zementieren, statt ihn zu finden.

## [1.2.0] - 2026-05-11

> **Deployment:** `php bin/console cache:clear`. Keine Datenbank-Migration, kein Storefront-Build (Änderungen an Tests und Doku).

### Hinzugefügt
- **Deutlich mehr Tests:** 26 PHPUnit-Tests (vorher 1). Sie prüfen die kritischen Vertrags-Strings von `meta.html.twig` und `body.html.twig` (`TwigTemplateContractTest`), die Pflicht-Felder und die vollständige Übersetzung in `config.xml` (`ConfigXmlContractTest`) sowie die Eigenschaften des Plugin-Bootstraps (`PluginBootstrapTest`). Weicht künftig etwas ab, meldet die CI das rot: beim Sanitizer-Regex (`'</script' → '<\\/script'`), bei den Consent-Pfaden (`tcString` primär, `purposeConsents` sekundär), bei den Escape-Filtern (`|e('js')`, `|e('html_attr')`), beim Schutz gegen doppeltes Laden (`trackingLoaded`) und bei der Verweigerung ohne CMP (`window.__cmp === 'undefined'`).
- `composer.json`: explizites Constraint `php: >=8.2`. Vorher galt es nur implizit über das Constraint des Shopware-Cores, was nicht zur CI-Matrix auf PHP 8.3 passte.

### Geändert

## [1.1.1] - 2026-04-22

> **Deployment:** `php bin/console cache:clear` (Stabilisierung von Tooling und Tests, keine Verhaltensänderung).

### Geändert


## [1.1.0] - 2026-03-24

> **Deployment:** `php bin/console cache:clear`

### Geändert
- Consentmanager-API auf das bewährte Format `addEventListener(2, ...)` umgestellt
- Der Consent-Callback prüft jetzt das `success`-Flag, bevor er weiterarbeitet

### Hinzugefügt
- Config-Feld „Consentmanager Marketing-Purpose-ID": Das Tracking lädt nur, wenn der konfigurierte Zweck erlaubt ist
- Unterstützung für das Event `consentapplied` (wiederkehrende Besucher mit gespeichertem Consent)

## [1.0.0] - 2026-03-24

> **Deployment:** `php bin/console cache:clear` (Erstinstallation)

### Hinzugefügt
- Integration von Google Tag Manager (GTM-ID konfigurierbar)
- Integration von Microsoft UET (aktivierbar/deaktivierbar, Tag-ID konfigurierbar)
- Benutzerdefinierter Tracking-Code (Freitextfeld für Meta Pixel, TikTok u. a.)
- DSGVO-konforme Consent-Integration über den Consentmanager (`window.__cmp`)
- Consent-Handling für Erstbesucher (`useractioncomplete`) und Wiederkehrende (`consentapplied`)
- Fallback: direktes Laden, wenn kein Consentmanager vorhanden ist
- GTM-`<noscript>`-Fallback im `<body>` für Umgebungen ohne JavaScript
- Admin-Konfiguration mit Hilfstext und Sicherheitshinweisen
- Shopware System Config pro Sales Channel überschreibbar
