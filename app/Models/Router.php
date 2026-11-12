<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Router extends Model {
    protected $table = 'routers';
    protected $fillable = ['nama_router','lokasi','ip_address','api_port','username','password_encrypted','identity','jenis','status','last_connected_at'];
    protected $hidden = ['password_encrypted'];
    protected $casts = ['last_connected_at'=>'datetime', 'password_encrypted'=>'encrypted'];
    public function mikrotikLogs(): HasMany { return $this->hasMany(MikrotikLog::class); }
}
