<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class TagihanDetail extends Model {
    protected $table = 'tagihan_detail';
    protected $fillable = ['tagihan_id','deskripsi','qty','harga_satuan','subtotal'];
    public function tagihan(): BelongsTo { return $this->belongsTo(Tagihan::class); }
}
