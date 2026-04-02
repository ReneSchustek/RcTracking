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

**Eigener Tracking-Code**
Für alles andere: Meta Pixel, TikTok, Affiliate-Tracking. Einfach den JavaScript-Code ins Textfeld, das Plugin übernimmt die Einbindung.

---

## DSGVO

Das Plugin wartet, bis der Nutzer eine Entscheidung getroffen hat – egal ob er gerade zum ersten Mal auf der Seite ist oder wiederkommt. Wer Marketing ablehnt, bekommt kein Tracking. Wer schon beim letzten Besuch zugestimmt hat, muss das nicht nochmal tun.

Optional lässt sich eine Marketing-Purpose-ID aus dem Consentmanager hinterlegen. Damit wird das Tracking nur geladen, wenn genau dieser Zweck erlaubt wurde – nicht bei „technisch notwendig" allein.

Kein Consentmanager vorhanden? Dann lädt das Tracking direkt. Für Entwicklungsumgebungen oder Shops, die bewusst ohne CMP arbeiten.

---

## Installation

```bash
bin/console plugin:refresh
bin/console plugin:install --activate RcTracking
bin/console cache:clear
bin/console theme:compile
```

Danach im Admin unter **Einstellungen → Plugins → RcTracking** konfigurieren.

---

## Voraussetzungen

- Shopware 6.7 / 6.8
- PHP 8.2+

---

## Lizenz

MIT
