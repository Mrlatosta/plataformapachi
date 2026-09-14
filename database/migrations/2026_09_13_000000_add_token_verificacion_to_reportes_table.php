<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reportes', function (Blueprint $table) {
            $table->string('token_verificacion', 40)->nullable()->unique();
        });

        // Los reportes existentes tambien reciben token para que su QR funcione al reimprimirlos
        DB::table('reportes')
            ->whereNull('token_verificacion')
            ->orderBy('id')
            ->select('id')
            ->each(function ($reporte) {
                DB::table('reportes')
                    ->where('id', $reporte->id)
                    ->update(['token_verificacion' => Str::random(40)]);
            });
    }

    public function down(): void
    {
        Schema::table('reportes', function (Blueprint $table) {
            $table->dropUnique(['token_verificacion']);
            $table->dropColumn('token_verificacion');
        });
    }
};
