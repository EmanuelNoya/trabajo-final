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
        Schema::create('importancia_indicador', function (Blueprint $table) {
            $table->foreignId('modelo_id')->constrained('modelo');
            $table->enum('indicador', ['asistencia', 'calificaciones', 'entregas', 'participacion']);
            $table->decimal('importancia', 5, 4);

            $table->primary(['modelo_id', 'indicador']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('importancia_indicador');
    }
};
