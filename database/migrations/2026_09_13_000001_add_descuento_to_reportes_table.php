<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Porcentaje de descuento (0-100), heredado de la cotizacion al capturar
        Schema::table('reportes', function (Blueprint $table) {
            $table->decimal('descuento', 5, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('reportes', function (Blueprint $table) {
            $table->dropColumn('descuento');
        });
    }
};
