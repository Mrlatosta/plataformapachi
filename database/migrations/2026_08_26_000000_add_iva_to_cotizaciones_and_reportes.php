<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->boolean('aplica_iva')->default(false)->after('descuento');
            $table->decimal('iva', 10, 2)->default(0)->after('subtotal');
        });

        Schema::table('reportes', function (Blueprint $table) {
            $table->boolean('aplica_iva')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->dropColumn(['aplica_iva', 'iva']);
        });

        Schema::table('reportes', function (Blueprint $table) {
            $table->dropColumn('aplica_iva');
        });
    }
};
