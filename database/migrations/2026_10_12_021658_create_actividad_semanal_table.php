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
        Schema::create('actividad_semanal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscripcion_id')->constrained('inscripcion');
            $table->foreignId('carga_id')->constrained('carga_archivo');
            $table->unsignedTinyInteger('semana');
            $table->boolean('con_actividad');

            $table->unique(['carga_id', 'inscripcion_id', 'semana']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('actividad_semanal');
    }
};
