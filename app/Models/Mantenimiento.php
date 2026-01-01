<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Mantenimiento extends Model
{
    use HasFactory;

    protected $table = 'mantenimientos';

    protected $fillable = [
        'fecha_realizada',
        'observaciones',
        'realizado',
        'id_equipo',
        'id_cronograma',
        'id_usuario',
    ];

    protected $casts = [
        'fecha_realizada' => 'date',
        'realizado' => 'boolean',
    ];

    /**
     * Mantenimiento pertenece a un Equipo
     */
    public function equipo()
    {
        return $this->belongsTo(Equipo::class, 'id_equipo');
    }

    /**
     * Mantenimiento pertenece a un Cronograma
     */
    public function cronograma()
    {
        return $this->belongsTo(CronogramaMantenimiento::class, 'id_cronograma');
    }

    /**
     * Mantenimiento pertenece a un Usuario
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }
}
