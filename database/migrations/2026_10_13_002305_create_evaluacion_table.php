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
        Schema::create('evaluacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curso_id')->constrained('curso');
            $table->foreignId('carga_id')->constrained('carga_archivo');
            $table->string('denominacion', 120);
            $table->enum('tipo', ['trabajo_practico', 'actividad_practica', 'foro', 'examen_parcial', 'otro']);
            $table->date('fecha_vencimiento');

            $table->unique(['carga_id', 'denominacion']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluacion');
    }
};
