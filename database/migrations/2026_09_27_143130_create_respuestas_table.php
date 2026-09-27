<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('respuestas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jugador_id')->constrained('jugadores')->cascadeOnDelete();
            $table->foreignId('pregunta_id')->constrained()->cascadeOnDelete();
            $table->foreignId('opcion_id')->constrained('opciones')->cascadeOnDelete();
            $table->unsignedInteger('milisegundos');
            $table->boolean('es_correcta')->default(false);
            $table->unsignedInteger('puntos')->default(0);
            $table->timestamps();

            $table->unique(['jugador_id', 'pregunta_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('respuestas');
    }
};
