<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Persona extends Model
{
   use HasFactory;

    protected $table = 'personas';

    protected $fillable = [
        'nombre_completo',
        'id_area',
    ];

    /**
     * Relación: Persona pertenece a un Área
     */
    public function area()
    {
        return $this->belongsTo(Area::class, 'id_area');
    }

    /**
     * Relación: Persona tiene muchos Equipos
     */
    public function equipos()
    {
        return $this->hasMany(Equipo::class, 'id_persona');
    }
}
