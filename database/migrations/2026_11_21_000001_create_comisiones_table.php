<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * T12: comisiones por medico que refiere.
 *  - medicos: % de comision. Usuario y contraseña pasan a ser opcionales: solo
 *    los necesita el medico que entra al portal de resultados.
 *  - comisiones: una por orden con medico; guarda monto, % y comision del momento.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicos', function (Blueprint $table) {
            $table->decimal('porcentaje_comision', 5, 2)->default(0);
            $table->string('usuario')->nullable()->change();
            $table->string('password')->nullable()->change();
        });

        Schema::create('comisiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporte_id')->unique()->constrained('reportes')->cascadeOnDelete();
            // Un medico con comisiones no se borra (se desactiva) para no perder el historial
            $table->foreignId('medico_id')->constrained('medicos')->restrictOnDelete();
            $table->decimal('monto_base', 10, 2)->default(0);
            $table->decimal('porcentaje', 5, 2)->default(0);
            $table->decimal('comision', 10, 2)->default(0);
            $table->string('estado', 20)->default('pendiente');
            $table->timestamp('pagada_at')->nullable();
            $table->foreignId('pagada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['medico_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comisiones');

        Schema::table('medicos', function (Blueprint $table) {
            $table->dropColumn('porcentaje_comision');
        });
    }
};
