<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CaracteristicaEquipo extends Model
{
    use HasFactory;

    protected $table = 'caracteristica_equipos';

    protected $fillable = [
        'clave',
        'valor',
        'id_equipo',
    ];

    /**
     * Característica pertenece a un Equipo
     */
    public function equipo()
    {
        return $this->belongsTo(Equipo::class, 'id_equipo');
    }
}
