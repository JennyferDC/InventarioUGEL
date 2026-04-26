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
        Schema::create('item_cronogramas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_oficinas')->constrained('oficinas')->onDelete('restrict');
            $table->foreignId('id_cronograma')->constrained('cronograma_mantenimientos')->onDelete('cascade');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->string('actividad');
            $table->enum('estado', ['Pendiente', 'Cancelado', 'Completado'])->default('Pendiente');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_cronogramas');
    }
};
