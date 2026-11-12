<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Langganan extends Model {
    protected $table = 'langganan';
    protected $fillable = ['pelanggan_id','paket_internet_id','tanggal_aktivasi','hari_aktivasi','tanggal_jatuh_tempo','tanggal_berakhir','masa_tenggang_hari','harga_snapshot','status','pppoe_username','pppoe_password_encrypted','ip_static','profile_router'];
    protected $hidden = ['pppoe_password_encrypted'];
    protected $casts = ['tanggal_aktivasi'=>'date','tanggal_jatuh_tempo'=>'date','tanggal_berakhir'=>'date','harga_snapshot'=>'decimal:2','pppoe_password_encrypted'=>'encrypted'];
    public function pelanggan(): BelongsTo { return $this->belongsTo(Pelanggan::class); }
    public function paketInternet(): BelongsTo { return $this->belongsTo(PaketInternet::class); }
    public function tagihan(): HasMany { return $this->hasMany(Tagihan::class); }
}
