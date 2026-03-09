<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->string('folio')->unique();
            $table->foreignId('paciente_id')->nullable()->constrained('pacientes')->nullOnDelete();
            $table->string('nombre_cliente');
            $table->string('email')->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('direccion', 500)->nullable();
            $table->date('fecha_cotizacion');
            $table->integer('vigencia')->default(30);
            $table->decimal('descuento', 5, 2)->default(0);
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->text('notas')->nullable();
            $table->enum('estado', ['pendiente_pago', 'pendiente_captura', 'completada'])->default('pendiente_pago');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizaciones');
    }
};
