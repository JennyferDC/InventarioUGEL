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
    
        Schema::create('mantenimientos', function (Blueprint $table) {
            $table->id(); // id : int

            $table->date('fecha_realizada'); // fecha_realizada : date
            $table->text('observaciones')->nullable(); // observaciones : text
            $table->boolean('realizado')->default(false); // realizado : boolean

            // FK -> equipos
            $table->foreignId('id_equipo')
                  ->constrained('equipos')
                  ->onDelete('restrict');

            // FK -> cronograma_mantenimientos
            $table->foreignId('id_cronograma')
                  ->constrained('cronograma_mantenimientos')
                  ->onDelete('restrict');

            // FK -> users
            $table->foreignId('id_usuario')
                  ->constrained('users')
                  ->onDelete('restrict');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mantenimientos');
    }
};
