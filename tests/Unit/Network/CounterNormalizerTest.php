<?php

namespace Tests\Unit\Network;

use App\Domain\Accounting\Services\CounterNormalizer;
use PHPUnit\Framework\TestCase;

class CounterNormalizerTest extends TestCase
{
    public function test_it_combines_octets_and_gigawords_into_64_bit_totals(): void
    {
        $this->assertSame(0, CounterNormalizer::total(0, 0));
        $this->assertSame(1000, CounterNormalizer::total(1000, 0));
        $this->assertSame(4294967296, CounterNormalizer::total(0, 1));
        $this->assertSame(4294968295, CounterNormalizer::total(999, 1));
        $this->assertSame(4294967295 + 4294967296, CounterNormalizer::total(4294967295, 1));
    }

    public function test_it_rejects_negative_radius_counters(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        CounterNormalizer::total(-1, 0);
    }

    public function test_delta_is_current_minus_previous(): void
    {
        $this->assertSame(['delta' => 500, 'valid' => true], CounterNormalizer::delta(1000, 1500));
        $this->assertSame(['delta' => 0, 'valid' => true], CounterNormalizer::delta(1000, 1000));
    }

    public function test_negative_delta_is_a_counter_reset_not_huge_traffic(): void
    {
        $result = CounterNormalizer::delta(5_000_000_000, 1200);

        $this->assertSame(0, $result['delta']);
        $this->assertFalse($result['valid']);
    }

    public function test_wrap_vector_counts_only_post_wrap_bytes_once(): void
    {
        // 4 GiB session total before wrap, 1.5 KiB after a 32-bit wrap.
        $previous = CounterNormalizer::total(4294967295, 0); // 4 294 967 295
        $current = CounterNormalizer::total(1536, 1);        // 4 294 967 296 + 1536

        $result = CounterNormalizer::delta($previous, $current);

        $this->assertTrue($result['valid']);
        $this->assertSame(1537, $result['delta']);
    }
}
