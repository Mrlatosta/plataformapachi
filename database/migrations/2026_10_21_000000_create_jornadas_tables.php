<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Captura rápida: una jornada (p. ej. antidoping en una empresa) predefine
        // los estudios y sus resultados por defecto para muchos colaboradores.
        Schema::create('jornadas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('empresa')->nullable();
            $table->timestamp('fecha_toma');
            $table->foreignId('medico_id')->nullable()->constrained('medicos')->nullOnDelete();
            $table->string('medico_solicitante')->nullable();
            $table->boolean('aplica_iva')->default(false);
            // [{estudio_id, tipo_muestra, metodo, elaboro, valido, precio, predeterminados: {examen_id: valor}}]
            $table->json('estudios');
            $table->text('notas')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('jornada_participantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jornada_id')->constrained('jornadas')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('sexo', 10)->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->unsignedSmallInteger('edad')->nullable();
            // {examen_id: resultado}; lo que no esté aquí toma el predeterminado de la jornada
            $table->json('resultados')->nullable();
            $table->string('estado', 12)->default('pendiente');
            $table->timestamp('capturado_en')->nullable();
            $table->foreignId('reporte_id')->nullable()->constrained('reportes')->nullOnDelete();
            $table->timestamps();

            $table->index(['jornada_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jornada_participantes');
        Schema::dropIfExists('jornadas');
    }
};
