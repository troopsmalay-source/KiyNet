<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class User extends Authenticatable {
    use HasFactory, Notifiable;
    protected $fillable = ['name','email','password','role_id','mitra_id','is_active'];
    protected $hidden = ['password','remember_token'];
    protected function casts(): array { return ['email_verified_at'=>'datetime','password'=>'hashed','is_active'=>'boolean']; }
    public function role(): BelongsTo { return $this->belongsTo(Role::class); }
    public function mitra(): BelongsTo { return $this->belongsTo(Mitra::class); }
    public function hasRole(string|array $roles): bool {
        $names = is_array($roles) ? $roles : [$roles];
        return in_array($this->role?->name, $names, true);
    }
}
