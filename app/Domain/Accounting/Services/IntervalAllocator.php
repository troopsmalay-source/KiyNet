<?php

namespace App\Domain\Accounting\Services;

use Carbon\CarbonImmutable;

/**
 * SPARSE SAMPLES: an accounting sample only proves that `delta` bytes were
 * transferred between the previous sample and this one. When that interval
 * crosses midnight the exact split is unknown, so it is distributed
 * proportionally to the seconds spent in each UTC day (deterministic,
 * reproducible, and flagged as estimated by the caller).
 */
final class IntervalAllocator
{
    /**
     * @return array<string, array{bytes: int, seconds: int, estimated: bool}>
     */
    public static function split(CarbonImmutable $start, CarbonImmutable $end, int $bytes): array
    {
        if ($end->lessThanOrEqualTo($start)) {
            return [$end->toDateString() => ['bytes' => $bytes, 'seconds' => 0, 'estimated' => true]];
        }

        $totalSeconds = (int) $start->diffInSeconds($end);

        if ($totalSeconds <= 0) {
            return [$end->toDateString() => ['bytes' => $bytes, 'seconds' => 0, 'estimated' => true]];
        }

        $result = [];
        $cursor = $start;
        $allocatedBytes = 0;
        $segments = [];

        while ($cursor->lessThan($end)) {
            $boundary = $cursor->startOfDay()->addDay();
            $segmentEnd = $boundary->lessThan($end) ? $boundary : $end;
            $segments[] = [$cursor, $segmentEnd];
            $cursor = $segmentEnd;
        }

        $estimated = $start->toDateString() !== $end->toDateString();

        foreach ($segments as $index => [$segmentStart, $segmentEnd]) {
            $seconds = (int) $segmentStart->diffInSeconds($segmentEnd);
            $isLast = $index === array_key_last($segments);

            $segmentBytes = $isLast
                ? $bytes - $allocatedBytes
                : intdiv($bytes * $seconds, $totalSeconds);

            $date = $segmentStart->toDateString();
            $existing = $result[$date] ?? ['bytes' => 0, 'seconds' => 0, 'estimated' => $estimated];
            $result[$date] = [
                'bytes' => $existing['bytes'] + $segmentBytes,
                'seconds' => $existing['seconds'] + $seconds,
                'estimated' => $estimated,
            ];

            $allocatedBytes += $segmentBytes;
        }

        return $result;
    }
}
