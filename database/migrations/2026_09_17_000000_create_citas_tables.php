<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Agenda: fecha y horas se guardan como hora local de Cancún (sin zona)
        Schema::create('citas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->nullable()->constrained('pacientes')->nullOnDelete();
            // Datos de contacto propios: muchas citas llegan por WhatsApp sin paciente registrado
            $table->string('nombre');
            $table->string('telefono', 20)->nullable();
            $table->string('email')->nullable();
            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->unsignedSmallInteger('duracion_minutos')->default(15);
            $table->string('modalidad', 12)->default('laboratorio');
            $table->string('direccion', 500)->nullable();
            $table->string('estado', 12)->default('programada');
            $table->string('origen', 12)->default('whatsapp');
            $table->text('indicaciones')->nullable();
            $table->text('notas')->nullable();
            $table->decimal('total_estimado', 10, 2)->default(0);
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['fecha', 'hora_inicio']);
            $table->index('estado');
        });

        // Estudios de la cita con el precio del momento en que se agendó
        Schema::create('cita_estudios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cita_id')->constrained('citas')->cascadeOnDelete();
            $table->foreignId('estudio_id')->nullable()->constrained('estudios')->nullOnDelete();
            $table->string('nombre');
            $table->decimal('precio', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cita_estudios');
        Schema::dropIfExists('citas');
    }
};
