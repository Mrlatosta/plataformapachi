<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * T13: el metodo y el tipo de muestra se eligen de una lista al capturar.
 *  - estudios: otras opciones de cada lista (las de siempre siguen en tipo_muestra / metodo).
 *  - reporte_estudio: quien cambio la muestra o el metodo de esa orden y cuando.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estudios', function (Blueprint $table) {
            $table->json('opciones_muestra')->nullable();
            $table->json('opciones_metodo')->nullable();
        });

        Schema::table('reporte_estudio', function (Blueprint $table) {
            $table->foreignId('muestra_metodo_cambiado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('muestra_metodo_cambiado_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('reporte_estudio', function (Blueprint $table) {
            $table->dropConstrainedForeignId('muestra_metodo_cambiado_por');
            $table->dropColumn('muestra_metodo_cambiado_at');
        });

        Schema::table('estudios', function (Blueprint $table) {
            $table->dropColumn(['opciones_muestra', 'opciones_metodo']);
        });
    }
};
