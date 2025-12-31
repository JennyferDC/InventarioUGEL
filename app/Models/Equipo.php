<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Equipo extends Model
{
   use HasFactory;

    protected $table = 'equipos';

    protected $fillable = [
        'cod_informatica',
        'tipo',
        'estado',
        'fecha_disponible_uso',
        'vida_util_anios',
        'id_persona',
    ];

    /**
     * Relación: Equipo pertenece a una Persona
     */
    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id_persona');
    }

    /**
     * Relación: Equipo tiene muchas Características
     */
    public function caracteristicas()
    {
        return $this->hasMany(CaracteristicaEquipo::class, 'id_equipo');
    }

    /**
     * Relación: Equipo tiene muchos Mantenimientos
     */
    public function mantenimientos()
    {
        return $this->hasMany(Mantenimiento::class, 'id_equipo');
    }

    /**
     * Relación: Equipo tiene muchos movimientos en el historial
     */
    public function historialMovimientos()
    {
        return $this->hasMany(HistorialMovimiento::class, 'id_equipo');
    }
}
