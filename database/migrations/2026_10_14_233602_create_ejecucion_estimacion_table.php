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
        Schema::create('ejecucion_estimacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curso_id')->constrained('curso');
            $table->foreignId('modelo_id')->constrained('modelo');
            $table->foreignId('usuario_id')->constrained('usuario');
            $table->date('fecha_corte');
            $table->timestamps(); // created_at = fecha y hora de la ejecución
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ejecucion_estimacion');
    }
};
