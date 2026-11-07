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
        Schema::create('ejecucion_carga', function (Blueprint $table) {
            $table->foreignId('ejecucion_id')->constrained('ejecucion_estimacion');
            $table->foreignId('carga_id')->constrained('carga_archivo');

            $table->primary(['ejecucion_id', 'carga_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ejecucion_carga');
    }
};
