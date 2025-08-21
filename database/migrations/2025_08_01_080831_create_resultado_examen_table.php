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
        Schema::create('resultado_examen', function (Blueprint $table) {
    $table->id();
    $table->foreignId('reporte_estudio_id')
          ->constrained('reporte_estudio')  // Aquí pones el nombre exacto
          ->onDelete('cascade');
    $table->foreignId('examen_id')
          ->constrained('examenes') // Asumiendo que esta sí es plural
          ->onDelete('cascade');
    $table->string('resultado');
    $table->boolean('fuera_rango')->default(false);

    $table->timestamps();
});


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resultado_examen');
    }
};
