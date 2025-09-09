<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('reportes', function (Blueprint $table) {
            // Cambiar de date a timestamp (sin timezone en PostgreSQL)
            $table->timestamp('toma_muestra')->nullable()->change();
            $table->timestamp('fecha_reporte')->nullable()->change();
            $table->timestamp('fecha_validacion')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('reportes', function (Blueprint $table) {
            // Revertir de nuevo a date
            $table->date('toma_muestra')->nullable()->change();
            $table->date('fecha_reporte')->nullable()->change();
            $table->date('fecha_validacion')->nullable()->change();
        });
    }
};
