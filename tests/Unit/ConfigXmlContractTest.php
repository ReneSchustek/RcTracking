<?php

declare(strict_types=1);

namespace Ruhrcoder\RcTracking\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Vertragstests gegen config.xml: erzwingt i18n-Vollständigkeit (de-DE + en-GB)
 * und prüft, dass die fünf Konfigurations-Felder vorhanden und korrekt strukturiert sind.
 */
final class ConfigXmlContractTest extends TestCase
{
    private const CONFIG_PATH = __DIR__ . '/../../src/Resources/config/config.xml';

    private static function load(): \SimpleXMLElement
    {
        $xml = simplexml_load_file(self::CONFIG_PATH);
        self::assertNotFalse($xml, 'config.xml konnte nicht geladen werden');

        return $xml;
    }

    public function testCardTitleIsBilingual(): void
    {
        $card = self::load()->card;
        self::assertNotNull($card);

        $titles = $card->title;
        self::assertCount(2, $titles, 'card erwartet genau zwei <title>-Elemente (de-DE und en-GB)');

        $locales = [];
        foreach ($titles as $title) {
            $lang = (string) ($title['lang'] ?? '');
            $locales[] = $lang === '' ? 'de-DE' : $lang;
        }

        self::assertContains('de-DE', $locales);
        self::assertContains('en-GB', $locales);
    }

    /**
     * @return iterable<string, array{0:string}>
     */
    public static function expectedInputFieldNames(): iterable
    {
        yield 'gtmId' => ['gtmId'];
        yield 'uetEnabled' => ['uetEnabled'];
        yield 'uetId' => ['uetId'];
        yield 'customCode' => ['customCode'];
        yield 'cmpMarketingPurposeId' => ['cmpMarketingPurposeId'];
        yield 'beginCheckoutOnce' => ['beginCheckoutOnce'];
    }

    /**
     * @dataProvider expectedInputFieldNames
     */
    public function testExpectedInputFieldExists(string $expectedName): void
    {
        $found = false;
        // Über alle Karten: Der Schalter für den Kassengang steht auf einer eigenen.
        foreach (self::load()->xpath('//input-field') ?: [] as $field) {
            if ((string) $field->name === $expectedName) {
                $found = true;
                break;
            }
        }
        self::assertTrue($found, sprintf('Erwartetes Feld "%s" fehlt in config.xml', $expectedName));
    }

    public function testAllTextInputFieldsHaveBilingualLabel(): void
    {
        foreach (self::load()->card->{'input-field'} as $field) {
            $name = (string) $field->name;
            $labels = $field->label;
            self::assertCount(
                2,
                $labels,
                sprintf('Feld "%s" muss genau zwei <label>-Elemente haben (de-DE + en-GB)', $name)
            );

            $locales = [];
            foreach ($labels as $label) {
                $lang = (string) ($label['lang'] ?? '');
                $locales[] = $lang === '' ? 'de-DE' : $lang;
            }
            self::assertContains('de-DE', $locales, sprintf('Feld "%s" fehlt de-DE-Label', $name));
            self::assertContains('en-GB', $locales, sprintf('Feld "%s" fehlt en-GB-Label', $name));
        }
    }

    public function testTextInputFieldsHaveBilingualHelpText(): void
    {
        $expectedHelpText = ['gtmId', 'uetId', 'customCode', 'cmpMarketingPurposeId'];
        foreach (self::load()->card->{'input-field'} as $field) {
            $name = (string) $field->name;
            if (!in_array($name, $expectedHelpText, true)) {
                continue;
            }

            $helpTexts = $field->helpText;
            self::assertGreaterThanOrEqual(
                2,
                count($helpTexts),
                sprintf('Feld "%s" erwartet helpText in de-DE und en-GB', $name)
            );
        }
    }

    public function testUetEnabledHasBooleanDefault(): void
    {
        foreach (self::load()->card->{'input-field'} as $field) {
            if ((string) $field->name !== 'uetEnabled') {
                continue;
            }
            self::assertSame('bool', (string) $field['type']);
            self::assertSame('false', (string) $field->defaultValue);

            return;
        }
        self::fail('Feld uetEnabled nicht gefunden');
    }

    /**
     * Ab Werk aus: Die Installation auf Live darf nichts am Tracking ändern, bevor die Wbm-Module
     * angepasst sind.
     */
    public function testBeginCheckoutOnceIsOffByDefault(): void
    {
        $fields = self::load()->xpath("//input-field[name='beginCheckoutOnce']") ?: [];
        self::assertCount(1, $fields);
        self::assertSame('bool', (string) $fields[0]['type']);
        self::assertSame('false', (string) $fields[0]->defaultValue);
    }
}
