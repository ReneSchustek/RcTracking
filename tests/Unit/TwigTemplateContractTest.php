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
     * Ein <noscript>-iframe lädt bedingungslos: ohne JavaScript gibt es keine Möglichkeit,
     * vorher eine Einwilligung zu prüfen. Und ein Nutzer ohne JavaScript kann den
     * Consentmanager nicht bedienen -- der ist selbst JavaScript. Er kann also NIEMALS
     * einwilligen. Das Fallback würde ausgerechnet die Nutzer tracken, die per Konstruktion
     * nicht zustimmen können: Tracking ohne Einwilligung, entgegen DSGVO und TTDSG und
     * entgegen dem erklärten Prinzip dieses Plugins (siehe meta.html.twig, Consent-Guard).
     *
     * Eine serverseitige Consent-Prüfung hilft nicht: Wer kein JavaScript hat, kann kein
     * Consent-Cookie besitzen. Wer JavaScript hat, führt <noscript> nie aus.
     *
     * Ein `layout/body.html.twig` mit diesem Snippet würde ohnehin nie rendern, weil es ein
     * Template erweitert, das es in Shopware nicht gibt
     * (`@Storefront/storefront/layout/body.html.twig` -- den <body> definiert base.html.twig).
     * Ein Test, der nur festhält, dass das Template auf diese Datei zeigt, bliebe dabei grün und
     * zementierte den Fehler, statt ihn zu finden. Deshalb prüft dieser Test, dass es die Datei
     * nicht gibt.
     */
    public function testNoGtmNoscriptFallbackInThePlugin(): void
    {
        self::assertFileDoesNotExist(
            __DIR__ . '/../../src/Resources/views/storefront/layout/body.html.twig',
            'Das body-Template wurde bewusst entfernt. Siehe README, Abschnitt DSGVO.',
        );

        $directory = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(__DIR__ . '/../../src/Resources/views'),
        );

        foreach ($directory as $file) {
            if (!$file->isFile() || !str_ends_with((string) $file->getFilename(), '.twig')) {
                continue;
            }

            $content = file_get_contents((string) $file->getPathname());
            self::assertIsString($content);

            self::assertStringNotContainsString(
                'googletagmanager.com/ns.html',
                $content,
                'GTM-noscript-Fallback in ' . $file->getFilename() . ' -- lädt ohne Einwilligung.',
            );
        }
    }

    /**
     * Das Skript für „begin_checkout“ lädt nur mit eingeschaltetem Schalter und nur auf den drei Seiten
     * des Kassengangs; sonst sendete es auf jeder Seite, auf der Wbm Warenkorbdaten schreibt.
     */
    public function testBeginCheckoutScriptIsGuardedBySwitchAndCheckoutRoutes(): void
    {
        $base = file_get_contents(__DIR__ . '/../../src/Resources/views/storefront/base.html.twig');
        self::assertNotFalse($base);

        self::assertStringContainsString("sw_extends '@Storefront/storefront/base.html.twig'", $base);
        self::assertStringContainsString('{% block base_body_script %}', $base);
        self::assertStringContainsString('{{ parent() }}', $base);
        self::assertStringContainsString("config('RcTracking.config.beginCheckoutOnce')", $base);
        self::assertStringContainsString("'frontend.checkout.register.page': 'login'", $base);
        self::assertStringContainsString("'frontend.checkout.confirm.page': 'confirm'", $base);
        self::assertStringContainsString("'frontend.checkout.finish.page': 'finish'", $base);
        self::assertStringContainsString("asset('bundles/rctracking/begin-checkout.js', 'asset')", $base);
        self::assertStringContainsString(' defer', $base, 'Ohne defer liefe das Skript, bevor der Körper steht.');
    }
}
