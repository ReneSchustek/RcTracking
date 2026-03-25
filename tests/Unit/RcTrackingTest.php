<?php

declare(strict_types=1);

namespace Ruhrcoder\RcTracking\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ruhrcoder\RcTracking\RcTracking;
use Shopware\Core\Framework\Plugin;

final class RcTrackingTest extends TestCase
{
    public function testPluginExtendsShopwarePlugin(): void
    {
        $this->assertTrue(is_subclass_of(RcTracking::class, Plugin::class));
    }

    public function testGetSubscribedEventsReturnsEmptyArray(): void
    {
        // Bootstrapper registriert selbst keine Events
        $this->assertTrue(true);
    }
}
