<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * T2 / T6: cada estudio de la orden sabe si se imprime, en que estado va
     * (pendiente, capturado, validado, entregado) y quien lo capturo y valido.
     *
     * El estado por defecto es 'validado' para que las ordenes ya emitidas (y las
     * que genera Captura rapida, solo para administradores) sigan saliendo igual.
     */
    public function up(): void
    {
        Schema::table('reporte_estudio', function (Blueprint $table) {
            $table->boolean('incluir_en_reporte')->default(true);
            $table->string('estado', 20)->default('validado');
            $table->foreignId('capturado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('capturado_at')->nullable();
            $table->foreignId('validado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validado_at')->nullable();
            $table->timestamp('entregado_at')->nullable();
        });

        Schema::table('reportes', function (Blueprint $table) {
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reportes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('creado_por');
        });

        Schema::table('reporte_estudio', function (Blueprint $table) {
            $table->dropConstrainedForeignId('capturado_por');
            $table->dropConstrainedForeignId('validado_por');
            $table->dropColumn(['incluir_en_reporte', 'estado', 'capturado_at', 'validado_at', 'entregado_at']);
        });
    }
};
