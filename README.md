# RcTracking

DSGVO-konformes Shopware 6.7 Plugin zur zentralen Verwaltung von Tracking-Skripten.

## Unterstützte Tracking-Dienste

- **Google Tag Manager (GTM)** – Container-ID konfigurierbar
- **Microsoft UET (Bing Ads)** – aktivierbar/deaktivierbar, Tag-ID konfigurierbar
- **Benutzerdefinierter Code** – freies JavaScript-Feld (Meta Pixel, TikTok, Affiliate etc.)

## DSGVO-Logik

Alle Skripte werden erst nach Nutzer-Consent über den Consentmanager geladen.
Fallback: Wenn kein Consentmanager vorhanden, wird das Tracking direkt geladen.

## Voraussetzungen

- Shopware 6.7.x
- PHP 8.3
- Composer 2.x

## Installation

```bash
bin/console plugin:refresh
bin/console plugin:install --activate RcTracking
bin/console cache:clear
bin/console theme:compile
```

## Konfiguration

Im Shopware Admin unter **Einstellungen → Plugins → RcTracking**:

| Feld | Beschreibung |
|------|--------------|
| GTM ID | Google Tag Manager Container-ID (z. B. `GTM-XXXXXXX`) |
| Microsoft UET aktiv | Tracking aktivieren/deaktivieren |
| Microsoft UET ID | Bing Tracking Tag-ID |
| Benutzerdefinierter Code | Freier JavaScript-Code |

## Entwicklung

```bash
composer install
composer quality      # CS-Check + PHPStan + Tests
composer test         # Nur Tests
composer phpstan      # Nur Statische Analyse
```

## Lizenz

MIT – siehe [LICENSE](LICENSE)

## Autor

Ruhrcoder (René Schustek) – [ruhrcoder.de](https://ruhrcoder.de)
