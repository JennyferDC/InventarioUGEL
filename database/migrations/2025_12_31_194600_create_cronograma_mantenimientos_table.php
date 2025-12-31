<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cronograma_mantenimientos', function (Blueprint $table) {
            $table->id(); // id : int
            $table->integer('anio'); // anio : int
            $table->date('fecha_inicio'); // fecha_inicio : date
            $table->date('fecha_fin'); // fecha_fin : date

            // FK -> areas
            $table->foreignId('id_area')
                  ->constrained('areas')
                  ->onDelete('restrict');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cronograma_mantenimientos');
    }
};
