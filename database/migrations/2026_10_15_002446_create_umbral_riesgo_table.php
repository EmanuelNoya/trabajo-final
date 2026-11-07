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
        Schema::create('umbral_riesgo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrera_id')->constrained('carrera');
            $table->foreignId('usuario_id')->constrained('usuario');
            $table->decimal('umbral_medio', 5, 4);
            $table->decimal('umbral_alto', 5, 4);
            $table->date('fecha_desde');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('umbral_riesgo');
    }
};
