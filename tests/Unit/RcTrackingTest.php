<?php

declare(strict_types=1);

namespace Ruhrcoder\RcTracking\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ruhrcoder\RcTracking\RcTracking;

/**
 * Smoke-Test: Plugin-Klasse und Namespace korrekt gesetzt.
 */
final class RcTrackingTest extends TestCase
{
    public function testPluginHasCorrectClass(): void
    {
        self::assertSame('Ruhrcoder\\RcTracking\\RcTracking', RcTracking::class);
    }
}
