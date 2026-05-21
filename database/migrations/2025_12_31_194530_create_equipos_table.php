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
            $table->id();

            $table->string('cod_informatica')->nullable();
            $table->string('cod_patrimonial')->nullable();
            $table->string('nombre')->nullable();
            $table->string('nombre_usuario')->nullable();
            $table->string('tipo')->nullable();
            $table->string('estado')->nullable();
            $table->string('categoria')->default('equipo')->nullable();
            $table->string('clasificacion')->nullable();
            $table->string('ip')->nullable();

            $table->date('fecha_ingreso')->nullable();
            $table->date('fecha_disponible_uso')->nullable();
            $table->integer('vida_util_anios')->nullable();
            $table->text('observacion_tecnica')->nullable();

            // FK -> personas
            $table->foreignId('id_persona')
                  ->nullable()
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
