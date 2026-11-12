<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MikrotikLog extends Model {
    protected $table='mikrotik_logs';
    protected $fillable=['router_id','pelanggan_id','aksi','status','request_data','response_data'];
    protected $casts=['request_data'=>'array'];
}
