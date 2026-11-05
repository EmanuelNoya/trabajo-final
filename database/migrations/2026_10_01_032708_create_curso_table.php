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
        Schema::create('curso', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrera_id')->constrained('carrera');
            $table->foreignId('docente_id')->constrained('usuario');
            $table->string('asignatura', 120);
            $table->string('comision', 10);
            $table->string('periodo_lectivo', 20);
            $table->string('modalidad', 40);
            $table->date('fecha_inicio');
            $table->date('fecha_fin');

            $table->unique(['asignatura', 'comision', 'periodo_lectivo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('curso');
    }
};
