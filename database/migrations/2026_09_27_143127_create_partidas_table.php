<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partidas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuestionario_id')->constrained()->cascadeOnDelete();
            $table->string('pin', 6)->unique();
            $table->string('estado')->default('esperando_jugadores');
            $table->boolean('modo_equipos')->default(false);
            $table->foreignId('pregunta_actual_id')->nullable()->constrained('preguntas')->nullOnDelete();
            $table->timestamp('pregunta_iniciada_en')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partidas');
    }
};
