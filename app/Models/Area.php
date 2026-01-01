<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Area extends Model
{
    use HasFactory;

    protected $table = 'areas';

    protected $fillable = [
        'nombre',
        'descripcion',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación: Area tiene muchas Personas
     */
    public function personas()
    {
        return $this->hasMany(Persona::class, 'id_area');
    }

    /**
     * Relación: Area tiene muchos Cronogramas de Mantenimiento
     */
    public function cronogramasMantenimiento()
    {
        return $this->hasMany(CronogramaMantenimiento::class, 'id_area');
    }
}
