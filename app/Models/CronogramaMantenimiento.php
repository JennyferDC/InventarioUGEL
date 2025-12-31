<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CronogramaMantenimiento extends Model
{
    use HasFactory;

    protected $table = 'cronograma_mantenimientos';

    protected $fillable = [
        'anio',
        'fecha_inicio',
        'fecha_fin',
        'id_area',
    ];

    /**
     * Cronograma pertenece a un Área
     */
    public function area()
    {
        return $this->belongsTo(Area::class, 'id_area');
    }

    /**
     * Cronograma tiene muchos mantenimientos
     */
    public function mantenimientos()
    {
        return $this->hasMany(Mantenimiento::class, 'id_cronograma');
    }
}
