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
        // database/migrations/xxxx_xx_xx_create_examenes_table.php
        Schema::create('examenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudio_id')->constrained()->onDelete('cascade');
            $table->string('nombre_examen');
            $table->string('unidad')->nullable();
            $table->string('valor_referencia')->nullable();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
{
    Schema::dropIfExists('examenes');
}


    
};
