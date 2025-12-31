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
        Schema::create('caracteristica_equipos', function (Blueprint $table) {
            $table->id(); // id : int
            $table->string('clave'); // clave : varchar
            $table->string('valor'); // valor : varchar

            // FK -> equipos
            $table->foreignId('id_equipo')
                  ->constrained('equipos')
                  ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('caracteristica_equipos');
    }
};
