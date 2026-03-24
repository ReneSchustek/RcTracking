# Changelog

Alle wesentlichen Änderungen an diesem Projekt werden hier dokumentiert.
Format basiert auf [Keep a Changelog](https://keepachangelog.com/de/1.0.0/).

## [1.1.0] – 2026-03-24

### Geändert
- Consentmanager-API auf bewährtes Format `addEventListener(2, ...)` umgestellt
- Consent-Callback prüft jetzt `success`-Flag vor Verarbeitung

### Hinzugefügt
- Config-Feld „Consentmanager Marketing-Purpose-ID": Tracking lädt nur wenn konfigurierten Zweck erlaubt
- Unterstützung für `consentapplied`-Event (wiederkehrende Besucher mit gespeichertem Consent)

## [1.0.0] – 2026-03-24

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
