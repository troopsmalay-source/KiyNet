<?php

namespace App\Domain\Accounting\Services;

use InvalidArgumentException;

/**
 * RADIUS octet counters wrap at 2^32; Gigawords hold the high 32 bits.
 * Never derive traffic with 32-bit arithmetic and never SUM cumulative rows.
 */
final class CounterNormalizer
{
    public const WRAP = 4294967296; // 2^32

    public static function total(int $octets, int $gigawords): int
    {
        if ($octets < 0 || $gigawords < 0) {
            throw new InvalidArgumentException('RADIUS octet counters cannot be negative.');
        }

        return $gigawords * self::WRAP + $octets;
    }

    /**
     * Delta between two cumulative totals. Negative values are a counter
     * reset/reboot anomaly, never traffic.
     *
     * @return array{delta: int, valid: bool}
     */
    public static function delta(int $previousTotal, int $currentTotal): array
    {
        $delta = $currentTotal - $previousTotal;

        if ($delta < 0) {
            return ['delta' => 0, 'valid' => false];
        }

        return ['delta' => $delta, 'valid' => true];
    }
}
