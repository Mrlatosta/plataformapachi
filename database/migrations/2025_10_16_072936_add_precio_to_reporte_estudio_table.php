<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('reporte_estudio', function (Blueprint $table) {
            if (!Schema::hasColumn('reporte_estudio', 'precio')) {
                $table->decimal('precio', 8, 2)->default(0)->after('valido');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reporte_estudio', function (Blueprint $table) {
            $table->dropColumn('precio');
        });
    }
};

