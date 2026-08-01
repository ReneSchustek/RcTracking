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
        self::assertTrue(is_subclass_of(RcTracking::class, Plugin::class));
    }
}
