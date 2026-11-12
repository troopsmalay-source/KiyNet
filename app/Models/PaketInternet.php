<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class PaketInternet extends Model {
    protected $table = 'paket_internet';
    protected $fillable = ['kode_paket','nama_paket','download_mbps','upload_mbps','harga','deskripsi','is_active'];
    protected $casts = ['harga'=>'decimal:2','is_active'=>'boolean'];
    public function langganan(): HasMany { return $this->hasMany(Langganan::class); }
}
