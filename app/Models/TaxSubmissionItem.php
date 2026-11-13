<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxSubmissionItem extends Model
{
    protected $fillable = ['tax_submission_id', 'description', 'amount_idr'];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(TaxSubmission::class, 'tax_submission_id');
    }
}
