<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Mitra extends Model {
    use SoftDeletes;
    protected $table = 'mitra';
    protected $fillable = ['kode_mitra','nama_mitra','nama_pic','no_hp','email','alamat','status'];
    public function pelanggan(): HasMany { return $this->hasMany(Pelanggan::class); }
    public function users(): HasMany { return $this->hasMany(User::class); }
}
