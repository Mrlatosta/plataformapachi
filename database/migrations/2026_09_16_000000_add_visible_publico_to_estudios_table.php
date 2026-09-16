<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Marca los estudios que podran listarse en la pagina publica (/inicio).
        // Apagado por defecto: nada se publica hasta que se marque a mano.
        Schema::table('estudios', function (Blueprint $table) {
            $table->boolean('visible_publico')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('estudios', function (Blueprint $table) {
            $table->dropColumn('visible_publico');
        });
    }
};
