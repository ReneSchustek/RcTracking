<?php

declare(strict_types=1);

namespace Ruhrcoder\RcTracking\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Vertragstests gegen die Twig-Templates.
 *
 * Prüft kritische Strings/Patterns im Quelltext (keine Twig-Render-Engine nötig).
 * Drift bei Sanitizer, Escape-Filtern oder Consent-Pfaden wird sofort rot.
 */
final class TwigTemplateContractTest extends TestCase
{
    private const META_PATH = __DIR__ . '/../../src/Resources/views/storefront/layout/meta.html.twig';

    private static function meta(): string
    {
        $content = file_get_contents(self::META_PATH);
        self::assertNotFalse($content, 'meta.html.twig konnte nicht gelesen werden');

        return $content;
    }

    public function testMetaTemplateExtendsCoreMetaLayout(): void
    {
        self::assertStringContainsString("sw_extends '@Storefront/storefront/layout/meta.html.twig'", self::meta());
    }

    public function testMetaTemplateHooksLayoutHeadJavascriptTrackingBlock(): void
    {
        self::assertStringContainsString('block layout_head_javascript_tracking', self::meta());
        self::assertStringContainsString('{{ parent() }}', self::meta());
    }

    public function testMetaTemplateReadsAllFiveConfigKeys(): void
    {
        $meta = self::meta();
        self::assertStringContainsString("config('RcTracking.config.gtmId')", $meta);
        self::assertStringContainsString("config('RcTracking.config.uetEnabled')", $meta);
        self::assertStringContainsString("config('RcTracking.config.uetId')", $meta);
        self::assertStringContainsString("config('RcTracking.config.customCode')", $meta);
        self::assertStringContainsString("config('RcTracking.config.cmpMarketingPurposeId')", $meta);
    }

    public function testMetaTemplateEscapesGtmIdAsJs(): void
    {
        self::assertStringContainsString("gtmId|e('js')", self::meta());
    }

    public function testMetaTemplateEscapesUetIdAsJs(): void
    {
        self::assertStringContainsString("uetId|e('js')", self::meta());
    }

    public function testMetaTemplateEscapesMarketingPurposeIdAsJs(): void
    {
        self::assertStringContainsString("marketingPurposeId|e('js')", self::meta());
    }

    public function testMetaTemplateSanitizesCustomCodeCloseTag(): void
    {
        // Twig-Replace: '</script' → '<\\/script' (zwei literale Backslashes in der Datei,
        // damit der Browser im JS-Kontext nichts ausführt). Single-Quoted PHP-Literal
        // benötigt vier Backslashes, um zwei zu matchen.
        self::assertStringContainsString("replace({'</script': '<\\\\/script'})", self::meta());
        self::assertStringContainsString('|raw', self::meta());
    }

    public function testMetaTemplateGatesOnPerPurposeConsentNotStringPresence(): void
    {
        $meta = self::meta();

        // Beide belegten Consent-Strukturen werden geprüft: IAB-TCF (purpose.consents[id])
        // und consentmanager nativ (purposeConsents[code]).
        self::assertStringContainsString('consentData.purpose.consents[', $meta);
        self::assertStringContainsString('consentData.purposeConsents[', $meta);

        // Das Gate darf NICHT an der bloßen Präsenz einer Consent-Zeichenkette hängen — die wird
        // auch bei Ablehnung gesetzt (live gegen echten consentmanager.net verifiziert: consentstring
        // 44 Zeichen nach „Alles ablehnen", purposeConsents = nur technisch notwendig).
        self::assertStringNotContainsString('tcString.length', $meta);
        self::assertStringNotContainsString('consentstring.length', $meta);
        self::assertStringNotContainsString("typeof consentData.tcString === 'string'", $meta);
    }

    public function testMetaTemplateDefaultDenyWithoutMarketingPurposeId(): void
    {
        $meta = self::meta();
        // Ohne konfigurierte Marketing-Purpose-ID lädt nichts (Default-Deny) — als Twig-Zweig abgesichert.
        self::assertStringContainsString('{% if not marketingPurposeId %}', $meta);
        self::assertStringContainsString('Default-Deny', $meta);
    }

    public function testMetaTemplateRefusesTrackingWithoutAnyCmpApi(): void
    {
        $meta = self::meta();
        // Verweigert, wenn WEDER die native (__cmp) NOCH die TCF-API (__tcfapi) vorhanden ist.
        self::assertStringContainsString('typeof window.__tcfapi', $meta);
        self::assertStringContainsString('typeof window.__cmp', $meta);
        self::assertStringContainsString('!hasTcf && !hasCmp', $meta);
        self::assertStringContainsString('console.warn', $meta);
        self::assertStringContainsString('DSGVO', $meta);
    }

    public function testMetaTemplateGuardsAgainstDoubleLoad(): void
    {
        $meta = self::meta();
        self::assertStringContainsString('var trackingLoaded = false;', $meta);
        self::assertStringContainsString('if (trackingLoaded)', $meta);
    }

    public function testMetaTemplateFiltersTcfConsentEventStatus(): void
    {
        $meta = self::meta();
        // TCF-Status: Nutzeraktion abgeschlossen sowie gespeicherter Consent bei Wiederkehr.
        self::assertStringContainsString('useractioncomplete', $meta);
        self::assertStringContainsString('tcloaded', $meta);
    }

    public function testMetaTemplateWiresBothCmpApis(): void
    {
        $meta = self::meta();
        // IAB TCF v2: addEventListener mit Event-Typ 2.
        self::assertStringContainsString("window.__tcfapi('addEventListener', 2,", $meta);
        // Consentmanager nativ: aktueller Stand via getCMPData + Änderungen via addEventListener(fn, false).
        self::assertStringContainsString("window.__cmp('getCMPData', null,", $meta);
        self::assertStringContainsString("window.__cmp('addEventListener',", $meta);
    }

    public function testMetaTemplateSkipsWhenNoServiceConfigured(): void
    {
        $meta = self::meta();
        // Outer-Guard: rendert das gesamte Script nur, wenn mindestens ein Dienst konfiguriert ist.
        self::assertStringContainsString('{% if gtmId or (uetEnabled and uetId) or customCode %}', $meta);
    }

    /**
     * Das GTM-`<noscript>`-Fallback darf NICHT existieren -- und zwar dauerhaft nicht.
     *
     * Ein <noscript>-iframe laedt bedingungslos: ohne JavaScript gibt es keine Moeglichkeit,
     * vorher eine Einwilligung zu pruefen. Und ein Nutzer ohne JavaScript kann den
     * Consentmanager nicht bedienen -- der ist selbst JavaScript. Er kann also NIEMALS
     * einwilligen. Das Fallback wuerde ausgerechnet die Nutzer tracken, die per Konstruktion
     * nicht zustimmen koennen: Tracking ohne Einwilligung, entgegen DSGVO und TTDSG und
     * entgegen dem erklaerten Prinzip dieses Plugins (siehe meta.html.twig, Consent-Guard).
     *
     * Eine serverseitige Consent-Pruefung hilft nicht: Wer kein JavaScript hat, kann kein
     * Consent-Cookie besitzen. Wer JavaScript hat, fuehrt <noscript> nie aus.
     *
     * Historie: Bis 1.2.0 existierte `layout/body.html.twig` mit genau diesem Snippet. Es hat
     * nie gerendert, weil es ein Template erweiterte, das es in Shopware nicht gibt
     * (`@Storefront/storefront/layout/body.html.twig` -- den <body> definiert base.html.twig).
     * Der Vorgaenger dieses Tests hat genau das GRUEN geprueft: Er nagelte fest, dass das
     * Template auf eine nicht existierende Datei zeigt. Ein Test kann einen Bug auch
     * zementieren, statt ihn zu finden.
     */
    public function testKeinGtmNoscriptFallbackImPlugin(): void
    {
        self::assertFileDoesNotExist(
            __DIR__ . '/../../src/Resources/views/storefront/layout/body.html.twig',
            'Das body-Template wurde bewusst entfernt. Siehe README, Abschnitt DSGVO.',
        );

        $verzeichnis = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(__DIR__ . '/../../src/Resources/views'),
        );

        foreach ($verzeichnis as $datei) {
            if (!$datei->isFile() || !str_ends_with((string) $datei->getFilename(), '.twig')) {
                continue;
            }

            $inhalt = file_get_contents((string) $datei->getPathname());
            self::assertIsString($inhalt);

            self::assertStringNotContainsString(
                'googletagmanager.com/ns.html',
                $inhalt,
                'GTM-noscript-Fallback in ' . $datei->getFilename() . ' -- laedt ohne Einwilligung.',
            );
        }
    }
}
