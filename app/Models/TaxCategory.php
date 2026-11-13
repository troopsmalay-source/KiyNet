<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxCategory extends Model
{
    protected $fillable = ['code', 'name', 'rate_basis_points', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function submissions(): HasMany
    {
        return $this->hasMany(TaxSubmission::class);
    }
}
