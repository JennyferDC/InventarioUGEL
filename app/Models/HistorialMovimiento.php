<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HistorialMovimiento extends Model
{
    use HasFactory;

    protected $table = 'historial_movimientos';

    protected $fillable = [
        'tipo_accion',
        'descripcion',
        'fecha_hora',
        'id_usuario',
        'id_equipo',
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
    ];

    /**
     * Movimiento realizado por un Usuario
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    /**
     * Movimiento puede afectar a un Equipo
     */
    public function equipo()
    {
        return $this->belongsTo(Equipo::class, 'id_equipo');
    }
}
