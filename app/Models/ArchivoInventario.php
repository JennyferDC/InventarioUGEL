<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArchivoInventario extends Model
{
    use HasFactory;

    protected $table = 'archivo_inventarios';

    protected $fillable = [
        'nombre_archivo',
        'ruta',
        'fecha_carga',
        'id_usuario',
    ];

    protected $casts = [
        'fecha_carga' => 'date',
    ];

    /**
     * Archivo pertenece a un Usuario
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }
}