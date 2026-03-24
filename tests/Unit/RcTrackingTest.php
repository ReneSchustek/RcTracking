<?php

declare(strict_types=1);

namespace Ruhrcoder\RcTracking\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ruhrcoder\RcTracking\RcTracking;

/**
 * Smoke-Test: Plugin-Klasse muss instanziierbar sein.
 */
final class RcTrackingTest extends TestCase
{
    public function testPluginClassExists(): void
    {
        self::assertTrue(class_exists(RcTracking::class));
    }
}
