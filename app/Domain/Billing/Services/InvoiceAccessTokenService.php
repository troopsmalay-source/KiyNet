<?php

namespace App\Domain\Billing\Services;

use App\Models\InvoiceAccessToken;
use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Support\Str;

class InvoiceAccessTokenService
{
    /**
     * @return array{token: string, model: InvoiceAccessToken}
     */
    public function issue(Tagihan $invoice, ?User $actor): array
    {
        $raw = Str::random(64);
        $days = (int) config('billing.public_token_days', 30);

        $model = InvoiceAccessToken::create([
            'tagihan_id' => $invoice->id,
            'token_hash' => hash('sha256', $raw),
            'expires_at' => now()->addDays($days),
            'created_by' => $actor?->id,
        ]);

        return ['token' => $raw, 'model' => $model];
    }

    public function findByRawToken(string $raw): ?InvoiceAccessToken
    {
        return InvoiceAccessToken::query()
            ->where('token_hash', hash('sha256', $raw))
            ->first();
    }
}
