<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComentarioEquipo extends Model
{
    use HasFactory;

    protected $table = 'comentario_equipos';

    protected $fillable = [
        'comentario',
        'id_equipo',
        'id_usuario',
    ];

    /**
     * Relación: El comentario pertenece a un Equipo.
     */
    public function equipo()
    {
        return $this->belongsTo(Equipo::class, 'id_equipo');
    }

    /**
     * Relación: El comentario fue realizado por un Usuario.
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }
}
