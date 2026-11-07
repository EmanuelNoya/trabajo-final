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
        Schema::create('modelo', function (Blueprint $table) {
            $table->id();
            $table->string('version', 20)->unique();
            $table->string('algoritmo', 60);
            $table->decimal('corte', 5, 4);
            $table->decimal('sensibilidad', 5, 4);
            $table->decimal('medida_f1', 5, 4);
            $table->date('fecha_entrenamiento');
            $table->string('ruta_archivo', 255);
            $table->char('hash_archivo', 64);
            $table->boolean('vigente')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modelo');
    }
};
