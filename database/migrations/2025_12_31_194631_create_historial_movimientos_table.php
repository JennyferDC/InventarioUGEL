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
        Schema::create('historial_movimientos', function (Blueprint $table) {
            $table->id(); // id : int

            $table->string('tipo_accion'); // tipo_accion : varchar
            $table->string('descripcion'); // descripcion : varchar
            $table->dateTime('fecha_hora'); // fecha_hora : datetime

            // FK -> users
            $table->foreignId('id_usuario')
                  ->constrained('users')
                  ->onDelete('restrict');

            // FK -> equipos (nullable)
            $table->foreignId('id_equipo')
                  ->nullable()
                  ->constrained('equipos')
                  ->onDelete('set null');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_movimientos');
    }
};
