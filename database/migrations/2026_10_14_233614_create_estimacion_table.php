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
        Schema::create('estimacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ejecucion_id')->constrained('ejecucion_estimacion');
            $table->foreignId('inscripcion_id')->constrained('inscripcion');
            $table->decimal('asistencia', 5, 4);
            $table->decimal('calificaciones', 5, 4);
            $table->decimal('entregas', 5, 4);
            $table->decimal('participacion', 5, 4);
            $table->decimal('probabilidad', 5, 4);

            $table->unique(['ejecucion_id', 'inscripcion_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estimacion');
    }
};
