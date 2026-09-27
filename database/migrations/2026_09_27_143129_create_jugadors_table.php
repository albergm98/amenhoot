<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jugadores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partida_id')->constrained()->cascadeOnDelete();
            $table->foreignId('equipo_id')->nullable()->constrained()->nullOnDelete();
            $table->string('apodo');
            $table->string('token', 64)->unique();
            $table->unsignedInteger('puntuacion')->default(0);
            $table->unsignedSmallInteger('racha')->default(0);
            $table->timestamps();

            $table->unique(['partida_id', 'apodo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jugadores');
    }
};
