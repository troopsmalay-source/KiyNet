<?php

namespace App\Domain\Accounting\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerUsageDaily extends Model
{
    protected $table = 'customer_usage_daily';

    public const COMPLETE = 'complete';

    public const ESTIMATED = 'estimated';

    public const GAP = 'gap';

    protected $fillable = [
        'customer_id', 'partner_id', 'usage_date', 'upload_bytes', 'download_bytes',
        'total_bytes', 'covered_seconds', 'sample_count', 'completeness',
        'calculation_version', 'calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'usage_date' => 'date',
            'upload_bytes' => 'integer',
            'download_bytes' => 'integer',
            'total_bytes' => 'integer',
            'covered_seconds' => 'integer',
            'sample_count' => 'integer',
            'calculation_version' => 'integer',
            'calculated_at' => 'datetime',
        ];
    }
}
