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
        Schema::table('usuario', function (Blueprint $table) {
            $table->foreignId('desactivado_por')->nullable()->after('activo')->constrained('usuario');
            $table->dateTime('fecha_desactivacion')->nullable()->after('desactivado_por');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usuario', function (Blueprint $table) {
            $table->dropConstrainedForeignId('desactivado_por');
            $table->dropColumn('fecha_desactivacion');
        });
    }
};
