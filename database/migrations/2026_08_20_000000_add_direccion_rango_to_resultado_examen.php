<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resultado_examen', function (Blueprint $table) {
            // 'alto' | 'bajo' | null -> define la flecha que se imprime en el reporte
            $table->string('direccion_rango', 10)->nullable()->after('fuera_rango');
        });
    }

    public function down(): void
    {
        Schema::table('resultado_examen', function (Blueprint $table) {
            $table->dropColumn('direccion_rango');
        });
    }
};
