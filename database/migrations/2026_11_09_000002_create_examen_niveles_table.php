<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * T5: intervalos de referencia por niveles (procalcitonina, perfiles hormonales).
     * desde/hasta vacios = sin limite. El nivel marcado como normal es el que usa
     * el marcado automatico fuera de rango (T4).
     */
    public function up(): void
    {
        Schema::create('examen_niveles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('examen_id')->constrained('examenes')->cascadeOnDelete();
            $table->unsignedInteger('orden')->default(0);
            $table->decimal('desde', 12, 4)->nullable();
            $table->decimal('hasta', 12, 4)->nullable();
            $table->string('interpretacion');
            $table->boolean('es_normal')->default(false);
            $table->timestamps();

            $table->index(['examen_id', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('examen_niveles');
    }
};
