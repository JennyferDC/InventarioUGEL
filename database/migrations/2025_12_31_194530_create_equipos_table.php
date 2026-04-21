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
        Schema::create('equipos', function (Blueprint $table) {
            $table->id(); // id : int

            $table->string('cod_informatica'); // cod_informatica : varchar

            $table->enum('tipo', [
                'PC',
                'LAPTOP',
                'TODO EN UNO',
                'COMPONENTE',
                'TECLADO',
                'MOUSE',
                'OTRO',
                'MONITOR',
            ]); // tipo : enum

            $table->enum('estado', [
                'LIBRE',
                'EN USO',
                'BAJA'
            ]); // estado : enum

            $table->date('fecha_ingreso')->nullable(); // fecha_ingreso : date
            $table->date('fecha_disponible_uso')->nullable(); // fecha_disponible_uso : date
            $table->integer('vida_util_anios')->nullable(); // vida_util_anios : int

            // FK -> personas
            $table->foreignId('id_persona')
                  ->constrained('personas')
                  ->onDelete('restrict');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipos');
    }
};
