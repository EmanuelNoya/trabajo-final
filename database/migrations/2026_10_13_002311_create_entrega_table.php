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
        Schema::create('entrega', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluacion_id')->constrained('evaluacion');
            $table->foreignId('inscripcion_id')->constrained('inscripcion');
            $table->foreignId('carga_id')->constrained('carga_archivo');
            $table->date('fecha_entrega')->nullable();
            $table->decimal('calificacion', 4, 2)->nullable();

            $table->unique(['evaluacion_id', 'inscripcion_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entrega');
    }
};
