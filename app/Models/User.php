<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    protected $fillable = [
        'usuario',
        'nombre_completo',
        'email',
        'password',
        'rol',
        'activo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'activo' => 'boolean',
    ];

    protected $appends = [
        'profile_photo_url',
    ];

    // ================= RELACIONES =================

    /**
     * Usuario registra muchos mantenimientos
     */
    public function mantenimientos()
    {
        return $this->hasMany(Mantenimiento::class, 'id_usuario');
    }

    /**
     * Usuario realiza muchos movimientos
     */
    public function historialMovimientos()
    {
        return $this->hasMany(HistorialMovimiento::class, 'id_usuario');
    }

    /**
     * Usuario carga muchos archivos de inventario
     */
    public function archivosInventario()
    {
        return $this->hasMany(ArchivoInventario::class, 'id_usuario');
    }
}