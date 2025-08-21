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
        Schema::table('reportes', function (Blueprint $table) {
    $table->date('toma_muestra')->nullable();
    $table->date('fecha_reporte')->nullable();
    $table->date('fecha_validacion')->nullable();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reportes', function (Blueprint $table) {
            //
        });
    }
};
