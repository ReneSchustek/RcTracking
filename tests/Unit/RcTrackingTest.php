<?php

declare(strict_types=1);

namespace Ruhrcoder\RcTracking\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ruhrcoder\RcTracking\RcTracking;
use Shopware\Core\Framework\Plugin;

/**
 * @deprecated Vollständige Bootstrap-Prüfung in PluginBootstrapTest. Bleibt für CI-Backwards-Kompatibilität.
 */
final class RcTrackingTest extends TestCase
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
}
