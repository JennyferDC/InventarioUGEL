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
        Schema::create('archivo_inventarios', function (Blueprint $table) {
            $table->id(); // id : int
            $table->string('nombre_archivo'); // nombre_archivo : varchar
            $table->string('ruta'); // ruta : varchar
            $table->date('fecha_carga'); // fecha_carga : date

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
        Schema::dropIfExists('archivo_inventarios');
    }
};
