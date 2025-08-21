<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reporte_estudio', function (Blueprint $table) {
    $table->id();
    $table->foreignId('reporte_id')->constrained('reportes')->onDelete('cascade');
    $table->foreignId('estudio_id')->constrained('estudios');
    $table->string('elaboro');
    $table->string('valido');
    $table->string('tipo_muestra');
    $table->string('metodo');
    $table->timestamps();
});


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reporte_estudio');
    }
};
