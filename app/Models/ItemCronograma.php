<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ItemCronograma extends Model
{
    use HasFactory;

    protected $table = 'item_cronogramas';

    protected $fillable = [
        'id_oficinas',
        'id_cronograma',
        'fecha_inicio',
        'fecha_fin',
        'actividad',
        'estado'
    ];

    public function oficina()
    {
        return $this->belongsTo(Oficina::class, 'id_oficinas');
    }

    public function cronograma()
    {
        return $this->belongsTo(CronogramaMantenimiento::class, 'id_cronograma');
    }
}
