<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * T1: cada orden queda ligada a un paciente para armar su historial.
     * nombre_busqueda (minusculas, sin acentos) permite buscar mientras se escribe
     * y detectar duplicados aunque cambien acentos o mayusculas.
     * Para ligar las ordenes viejas: php artisan pacientes:vincular-reportes
     */
    public function up(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            $table->string('nombre_busqueda')->nullable()->index();
        });

        Schema::table('reportes', function (Blueprint $table) {
            $table->foreignId('paciente_id')->nullable()->after('id')->constrained('pacientes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reportes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('paciente_id');
        });

        Schema::table('pacientes', function (Blueprint $table) {
            $table->dropColumn('nombre_busqueda');
        });
    }
};
