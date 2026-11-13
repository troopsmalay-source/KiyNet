<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxSubmission extends Model
{
    protected $fillable = ['mitra_id', 'tax_category_id', 'period', 'gross_amount_idr', 'tax_amount_idr', 'status', 'notes', 'submitted_at', 'reviewed_at', 'reviewed_by'];

    protected $casts = ['submitted_at' => 'datetime', 'reviewed_at' => 'datetime'];

    public function mitra(): BelongsTo
    {
        return $this->belongsTo(Mitra::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TaxCategory::class, 'tax_category_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TaxSubmissionItem::class);
    }
}
