<?php

namespace App\Domain\Accounting\Models;

use Illuminate\Database\Eloquent\Model;

class PartnerUsageDaily extends Model
{
    public const COMPLETE = 'complete';

    public const ESTIMATED = 'estimated';

    public const GAP = 'gap';

    protected $table = 'partner_usage_daily';

    protected $fillable = [
        'partner_id', 'usage_date', 'upload_bytes', 'download_bytes', 'total_bytes',
        'peak_download_mbps', 'peak_upload_mbps', 'completeness',
        'calculation_version', 'calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'usage_date' => 'date',
            'upload_bytes' => 'integer',
            'download_bytes' => 'integer',
            'total_bytes' => 'integer',
            'peak_download_mbps' => 'decimal:3',
            'peak_upload_mbps' => 'decimal:3',
            'calculation_version' => 'integer',
            'calculated_at' => 'datetime',
        ];
    }
}
