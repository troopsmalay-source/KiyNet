<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Invoice extends Model {
    protected $table = 'invoice';
    protected $fillable = ['tagihan_id','nomor_invoice','file_pdf','tanggal_terbit','snapshot_data','status'];
    protected $casts = ['tanggal_terbit'=>'datetime','snapshot_data'=>'array'];
    public function tagihan(): BelongsTo { return $this->belongsTo(Tagihan::class); }
}
