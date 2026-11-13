<?php

namespace Tests\Unit\Network;

use App\Domain\Accounting\Services\IntervalAllocator;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class IntervalAllocatorTest extends TestCase
{
    public function test_same_day_interval_stays_whole_and_is_not_estimated(): void
    {
        $result = IntervalAllocator::split(
            CarbonImmutable::parse('2026-11-12 10:00:00', 'UTC'),
            CarbonImmutable::parse('2026-11-12 10:05:00', 'UTC'),
            600,
        );

        $this->assertSame(['2026-11-12'], array_keys($result));
        $this->assertSame(600, $result['2026-11-12']['bytes']);
        $this->assertSame(300, $result['2026-11-12']['seconds']);
        $this->assertFalse($result['2026-11-12']['estimated']);
    }

    public function test_interval_straddling_midnight_splits_proportionally_and_is_estimated(): void
    {
        // 23:58 -> 00:02 = 120s before midnight, 120s after.
        $result = IntervalAllocator::split(
            CarbonImmutable::parse('2026-11-12 23:58:00', 'UTC'),
            CarbonImmutable::parse('2026-11-13 00:02:00', 'UTC'),
            1200,
        );

        $this->assertSame(600, $result['2026-11-12']['bytes']);
        $this->assertSame(600, $result['2026-11-13']['bytes']);
        $this->assertSame(120, $result['2026-11-12']['seconds']);
        $this->assertSame(120, $result['2026-11-13']['seconds']);
        $this->assertTrue($result['2026-11-12']['estimated']);
        $this->assertTrue($result['2026-11-13']['estimated']);
    }

    public function test_allocation_never_loses_or_invents_bytes(): void
    {
        $result = IntervalAllocator::split(
            CarbonImmutable::parse('2026-11-12 21:17:00', 'UTC'),
            CarbonImmutable::parse('2026-11-14 03:41:00', 'UTC'),
            1_000_003,
        );

        $this->assertSame(1_000_003, array_sum(array_column($result, 'bytes')));
    }

    public function test_zero_length_interval_assigns_all_bytes_to_the_sample_day(): void
    {
        $result = IntervalAllocator::split(
            CarbonImmutable::parse('2026-11-12 10:00:00', 'UTC'),
            CarbonImmutable::parse('2026-11-12 10:00:00', 'UTC'),
            700,
        );

        $this->assertSame(700, $result['2026-11-12']['bytes']);
        $this->assertTrue($result['2026-11-12']['estimated']);
    }
}
