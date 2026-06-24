<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reporte_estudio', function (Blueprint $table) {
            if (!Schema::hasColumn('reporte_estudio', 'orden')) {
                $table->integer('orden')->default(0)->after('reporte_id');
            }
        });

        // Backfill: usar el id como orden inicial para preservar el orden actual
        DB::statement('UPDATE reporte_estudio SET orden = id WHERE orden = 0');
    }

    public function down(): void
    {
        Schema::table('reporte_estudio', function (Blueprint $table) {
            $table->dropColumn('orden');
        });
    }
};
