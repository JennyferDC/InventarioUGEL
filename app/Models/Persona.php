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
        'celular',
        'estado',
        'id_oficina',
    ];

    /**
     * Relación: Persona pertenece a un Área o a una Oficina
     */
    public function oficina()
    {
        return $this->belongsTo(Oficina::class, 'id_oficina');
    }

    /**
     * Relación: Persona tiene muchos Equipos
     */
    public function equipos()
    {
        return $this->hasMany(Equipo::class, 'id_persona');
    }
}
