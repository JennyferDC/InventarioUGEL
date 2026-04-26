<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CronogramaMantenimiento extends Model
{
    use HasFactory;

    protected $table = 'cronograma_mantenimientos';

    protected $fillable = [
        'titulo',
        'descripcion',
    ];

    /**
     * Cronograma tiene muchos items
     */
    public function items()
    {
        return $this->hasMany(ItemCronograma::class, 'id_cronograma');
    }

    /**
     * Cronograma tiene muchos mantenimientos
     */
    public function mantenimientos()
    {
        return $this->hasMany(Mantenimiento::class, 'id_cronograma');
    }
}
