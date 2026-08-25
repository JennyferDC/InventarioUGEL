<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Equipo extends Model
{
   use HasFactory;

    protected $table = 'equipos';

    protected $fillable = [
        'cod_informatica',
        'cod_patrimonial',
        'cod_serial',
        'nombre',
        'descripcion',
        'nombre_usuario',
        'tipo',
        'estado',
        'fecha_ingreso',
        'fecha_disponible_uso',
        'vida_util_anios',
        'id_persona',
        'observacion_tecnica',
        'categoria',
        'ip',
        'clasificacion',
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
     * Relación: Equipo tiene muchos Comentarios
     */
    public function comentarios()
    {
        return $this->hasMany(ComentarioEquipo::class, 'id_equipo')->with('usuario:id,name,email')->latest();
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

    /**
     * Relación: Un equipo físico tiene muchos programas/softwares instalados.
     */
    public function programas()
    {
        return $this->belongsToMany(Equipo::class, 'equipo_programa', 'equipo_id', 'programa_id')->withTimestamps();
    }

    /**
     * Relación: Un programa/software está instalado en muchos equipos físicos.
     */
    public function equipos()
    {
        return $this->belongsToMany(Equipo::class, 'equipo_programa', 'programa_id', 'equipo_id')->withTimestamps();
    }
}
