<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Pelanggan extends Model {
    use SoftDeletes;
    protected $table = 'pelanggan';
    protected $fillable = ['mitra_id','kode_pelanggan','nama_pelanggan','no_hp','email','alamat_pasang','latitude','longitude','nik','status','catatan'];
    public function mitra(): BelongsTo { return $this->belongsTo(Mitra::class); }
    public function langganan(): HasMany { return $this->hasMany(Langganan::class); }
    public function tagihan(): HasMany { return $this->hasMany(Tagihan::class); }
}
