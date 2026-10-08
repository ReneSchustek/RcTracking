<?php

declare(strict_types=1);

namespace Ruhrcoder\RcTracking\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ruhrcoder\RcTracking\RcTracking;
use Shopware\Core\Framework\Plugin;

/**
 * Plugin-Bootstrap-Eigenschaften, die für Shopware-Lifecycle und CI-Discovery zwingend sind.
 */
final class PluginBootstrapTest extends TestCase
{
    public function testPluginExtendsShopwarePlugin(): void
    {
        $reflection = new \ReflectionClass(RcTracking::class);

        // Die Vererbung selbst prüft der Compiler; ein Test darauf kann nicht fehlschlagen.
        // Prüfbar ist dagegen, ob Shopware die Klasse tatsächlich laden kann: Eine abstrakte
        // Plugin-Klasse bricht erst beim Installieren, und dann steht der Shop.
        self::assertFalse($reflection->isAbstract(), 'Plugin-Klasse darf nicht abstrakt sein');
        self::assertTrue($reflection->isSubclassOf(Plugin::class), 'Plugin-Klasse muss von Plugin erben');
    }

    public function testPluginClassIsFinal(): void
    {
        $reflection = new \ReflectionClass(RcTracking::class);
        self::assertTrue($reflection->isFinal(), 'Plugin-Klasse muss final sein (Vererbung in Plugins unerwünscht)');
    }

    public function testPluginClassHasStrictTypesEnabled(): void
    {
        $reflection = new \ReflectionClass(RcTracking::class);
        $file = (string) $reflection->getFileName();
        $contents = file_get_contents($file);
        self::assertNotFalse($contents);
        self::assertStringContainsString('declare(strict_types=1);', $contents);
    }

    public function testPluginNamespaceFollowsRuhrcoderConvention(): void
    {
        // Pflicht: Ruhrcoder\Rc{PluginName}\ — niemals RuhrCoder (falsches C) oder ohne Vendor-Präfix.
        self::assertSame('Ruhrcoder\\RcTracking', (new \ReflectionClass(RcTracking::class))->getNamespaceName());
    }

    public function testPluginIconExists(): void
    {
        $iconPath = __DIR__ . '/../../src/Resources/config/plugin.png';
        self::assertFileExists($iconPath, 'plugin.png muss in src/Resources/config liegen');
    }
}
