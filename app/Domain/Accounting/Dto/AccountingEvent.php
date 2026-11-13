<?php

namespace App\Domain\Accounting\Dto;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * Normalized RADIUS accounting event.
 *
 * Octet counters in RADIUS are split into a 32-bit octet count and Gigawords.
 * The 64-bit total is computed here, never by the SQL layer.
 */
final class AccountingEvent
{
    public const START = 'start';

    public const INTERIM = 'interim';

    public const STOP = 'stop';

    public function __construct(
        public readonly int $nasDeviceId,
        public readonly string $nasCode,
        public readonly string $eventType,
        public readonly string $sessionId,
        public readonly ?string $uniqueId,
        public readonly string $username,
        public readonly CarbonImmutable $sampledAt,
        public readonly CarbonImmutable $startAt,
        public readonly int $inputOctets,
        public readonly int $outputOctets,
        public readonly int $inputGigawords,
        public readonly int $outputGigawords,
        public readonly ?int $sessionSeconds,
        public readonly ?string $framedIp,
        public readonly ?string $terminateCause,
        public readonly ?string $sourceEventId = null,
        public readonly ?string $rawRecordReference = null,
    ) {
    }

    /**
     * @param  array<string, mixed>  $attributes  RADIUS attributes keyed by attribute name
     */
    public static function fromRadiusAttributes(int $nasDeviceId, string $nasCode, array $attributes): self
    {
        $status = strtolower((string) ($attributes['Acct-Status-Type'] ?? ''));
        $eventType = match ($status) {
            'start' => self::START,
            'interim-update', 'interim_update', 'interim' => self::INTERIM,
            'stop' => self::STOP,
            default => throw new InvalidArgumentException("Unsupported Acct-Status-Type: {$status}"),
        };

        $sessionId = trim((string) ($attributes['Acct-Session-Id'] ?? ''));

        if ($sessionId === '') {
            throw new InvalidArgumentException('Acct-Session-Id is required.');
        }

        $sampledAt = self::timestamp($attributes['Event-Timestamp'] ?? $attributes['Timestamp'] ?? null);
        $startAt = self::timestamp($attributes['Acct-Start-Time'] ?? null) ?? $sampledAt;

        return new self(
            nasDeviceId: $nasDeviceId,
            nasCode: $nasCode,
            eventType: $eventType,
            sessionId: $sessionId,
            uniqueId: isset($attributes['Acct-Unique-Session-Id']) ? (string) $attributes['Acct-Unique-Session-Id'] : null,
            username: (string) ($attributes['User-Name'] ?? ''),
            sampledAt: $sampledAt,
            startAt: $startAt,
            inputOctets: (int) ($attributes['Acct-Input-Octets'] ?? 0),
            outputOctets: (int) ($attributes['Acct-Output-Octets'] ?? 0),
            inputGigawords: (int) ($attributes['Acct-Input-Gigawords'] ?? 0),
            outputGigawords: (int) ($attributes['Acct-Output-Gigawords'] ?? 0),
            sessionSeconds: isset($attributes['Acct-Session-Time']) ? (int) $attributes['Acct-Session-Time'] : null,
            framedIp: isset($attributes['Framed-IP-Address']) ? (string) $attributes['Framed-IP-Address'] : null,
            terminateCause: isset($attributes['Acct-Terminate-Cause']) ? (string) $attributes['Acct-Terminate-Cause'] : null,
            sourceEventId: isset($attributes['Acct-Session-Id']) && isset($attributes['Event-Timestamp'])
                ? $sessionId.'@'.$attributes['Event-Timestamp']
                : null,
        );
    }

    private static function timestamp(mixed $value): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return CarbonImmutable::createFromTimestampUTC((int) $value);
        }

        return CarbonImmutable::parse((string) $value)->utc();
    }
}
