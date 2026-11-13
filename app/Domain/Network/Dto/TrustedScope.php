<?php

namespace App\Domain\Network\Dto;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Server-side authorization scope. Never construct this from HTTP input.
 */
final class TrustedScope
{
    private function __construct(
        public readonly bool $central,
        public readonly ?int $partnerId,
    ) {
    }

    public static function central(): self
    {
        return new self(true, null);
    }

    public static function partner(int $partnerId): self
    {
        return new self(false, $partnerId);
    }

    public function isCentral(): bool
    {
        return $this->central;
    }

    public function allows(?int $partnerId): bool
    {
        if ($this->central) {
            return true;
        }

        return $partnerId !== null && $partnerId === $this->partnerId;
    }

    /**
     * Partner scope must not be able to read another partner's data. Use 404
     * for resource lookups to avoid ID enumeration, 403 for scope assertion.
     */
    public function assertCentral(): void
    {
        if (! $this->central) {
            throw new AccessDeniedHttpException('Aksi jaringan hanya untuk operator pusat.');
        }
    }

    public function assertAllows(?int $partnerId): void
    {
        if (! $this->allows($partnerId)) {
            throw new AccessDeniedHttpException('Di luar cakupan mitra Anda.');
        }
    }
}
