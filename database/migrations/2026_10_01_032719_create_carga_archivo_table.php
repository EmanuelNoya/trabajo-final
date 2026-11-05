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
        Schema::create('carga_archivo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curso_id')->constrained('curso');
            $table->foreignId('usuario_id')->constrained('usuario');
            $table->enum('tipo_archivo', ['nomina', 'actividad', 'calificaciones', 'foros']);
            $table->string('nombre_archivo', 255);
            $table->char('hash_archivo', 64);
            $table->unsignedInteger('filas_validas')->default(0);
            $table->unsignedInteger('filas_rechazadas')->default(0);
            $table->enum('estado', ['vigente', 'reemplazada'])->default('vigente');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carga_archivo');
    }
};
