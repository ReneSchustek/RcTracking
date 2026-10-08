# RcTracking

Ein Shopware 6 Plugin von [Ruhrcoder](https://ruhrcoder.de).

---

## Worum geht's?

Tracking-Skripte haben im Theme nichts verloren. Trotzdem landen sie dort – weil es schnell geht, weil es irgendwann mal jemand reingebaut hat, und weil es danach niemand mehr anfasst. Beim nächsten Theme-Update ist der Code weg. Oder er lädt, obwohl der Nutzer gerade Nein gesagt hat.

RcTracking löst das. Das Plugin verwaltet Google Tag Manager, Microsoft UET und beliebigen eigenen Tracking-Code zentral im Admin – und lädt nichts davon, bevor der Nutzer zugestimmt hat.

---

## Was kann es?

**Google Tag Manager**
Container-ID eintragen, fertig. GTM kümmert sich dann um den Rest.

**Microsoft UET (Bing Ads)**
An/Aus-Schalter und Tag-ID – mehr braucht es nicht.

**Kassenbeginn genau einmal**
Schalter „begin_checkout genau einmal je Kassengang“ (ab Werk aus). Sendet `begin_checkout` einmal je Warenkorb, auf der Anmelde- oder der Bestätigungsseite, mit den Warenkorbdaten von „Google Tag Manager + GA4 E-Commerce Tracking“ (Wbm). Dort an „Checkout Register Page“ und „Confirm Page“ die Eigenschaft „event“ entfernen; solange sie steht, sendet der Schalter nichts dazu. Braucht nach der Installation `assets:install` (macht `rc-rollout.sh`).

**Eigener Tracking-Code**
Für alles andere: Meta Pixel, TikTok, Affiliate-Tracking. Einfach den JavaScript-Code ins Textfeld, das Plugin übernimmt die Einbindung.

---

## DSGVO

Das Plugin wartet, bis der Nutzer eine Entscheidung getroffen hat – egal ob er gerade zum ersten Mal auf der Seite ist oder wiederkommt. Wer Marketing ablehnt, bekommt kein Tracking. Wer schon beim letzten Besuch zugestimmt hat, muss das nicht nochmal tun.

Maßgeblich ist die Marketing-Purpose-ID aus dem Consentmanager. Das Tracking lädt nur, wenn genau dieser Zweck erlaubt wurde – nicht bei „technisch notwendig" allein, und ausdrücklich **nicht**, nur weil überhaupt eine Entscheidung getroffen wurde. Ist keine Purpose-ID hinterlegt, lädt gar nichts (Default-Deny).

Das Feld nimmt die Kennung so entgegen, wie sie der jeweilige Consentmanager liefert: bei consentmanager.net den Purpose-Code (z. B. „c2"), im IAB-TCF-Modus die numerische Purpose-ID (z. B. „5"). Beide Betriebsarten werden unterstützt.

**Kein Consentmanager vorhanden? Dann lädt das Tracking gar nicht.** Ohne CMP gibt es keine Einwilligung, und ohne Einwilligung kein Tracking. Das Plugin protokolliert in diesem Fall eine Warnung in der Browser-Konsole und bleibt still.

### Warum es kein GTM-`<noscript>`-Fallback gibt

Google Tag Manager sieht neben dem Skript im `<head>` einen `<iframe>` in einem `<noscript>`-Block vor, der Nutzer ohne JavaScript erfassen soll. **Dieses Plugin rendert ihn bewusst nicht.**

Der Grund ist keine Bequemlichkeit, sondern Logik: Ein `<noscript>`-iframe lädt **bedingungslos** – ohne JavaScript gibt es keine Möglichkeit, vorher eine Einwilligung zu prüfen. Und ein Nutzer ohne JavaScript kann den Consentmanager gar nicht bedienen, denn der ist selbst JavaScript. **Er kann also niemals einwilligen.**

Das Fallback würde damit ausgerechnet die Nutzer tracken, die per Konstruktion nicht zustimmen können. Das ist kein Fallback, das ist Tracking ohne Einwilligung.

Auch eine serverseitige Prüfung hilft nicht: Wer kein JavaScript hat, kann kein Consent-Cookie besitzen (das CMP setzt es per JavaScript). Wer JavaScript hat, führt `<noscript>` nie aus. Das Snippet kann in **keinem** Fall legitim feuern.

Der Preis: Nutzer ohne JavaScript – ein Bruchteil eines Prozents – tauchen in GTM nicht auf. Das ist der Preis, den ein Consent-Management kostet.

---

## Installation

Das Plugin gehört als Ordner `custom/plugins/RcTracking` in die Shopware-Installation. Übertragen wird er von Hand — per FTP in dieses Verzeichnis kopieren; ein Composer-Paket gibt es nicht. Danach registriert Shopware ihn über die folgenden Befehle:

Als Archiv geht es auch ohne FTP: In der Administration unter **Erweiterungen → Meine Erweiterungen → Erweiterung hochladen** nimmt Shopware eine ZIP-Datei entgegen und legt sie selbst an die richtige Stelle; auf der Konsole tut `plugin:zip-import` dasselbe. Danach folgen dieselben Schritte wie unten. Wer das Archiv unter macOS packt, entfernt vorher den Ordner `__MACOSX` — sonst weist Shopware die Datei ab.

```bash
bin/console plugin:refresh
bin/console plugin:install --activate RcTracking
bin/console cache:clear
bin/console theme:compile
```

**Aktualisierungen laufen denselben Weg.** Den neuen Ordner per FTP über den alten legen, danach `plugin:refresh` und `plugin:update RcTracking`. Eine Aktualisierung von selbst gibt es nicht — ohne Composer-Paket und ohne Shopware-Store bleibt sie Sache des Betreibers. Vor einem Sprung über eine Hauptversion gehört ein Datenbank-Abzug dazu.

Danach im Admin unter **Einstellungen → Plugins → RcTracking** konfigurieren.

---

## Voraussetzungen

- Shopware 6.7 / 6.8
- PHP 8.2+

---

## Lizenz

MIT
