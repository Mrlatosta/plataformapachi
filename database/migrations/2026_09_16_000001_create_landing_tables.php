<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Imagenes de la pagina publica. Se guardan en la base de datos (base64)
        // porque el disco de Railway se borra en cada despliegue.
        Schema::create('landing_imagenes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('mime', 30);
            $table->unsignedInteger('ancho');
            $table->unsignedInteger('alto');
            $table->unsignedInteger('tamano');
            $table->string('alt', 150)->nullable();
            $table->longText('datos');
            $table->timestamps();
        });

        Schema::create('landing_promociones', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 120);
            $table->string('descripcion', 400)->nullable();
            $table->json('incluye')->nullable();
            $table->decimal('precio_anterior', 10, 2)->nullable();
            $table->decimal('precio_actual', 10, 2);
            $table->string('etiqueta', 30)->nullable();
            $table->string('etiqueta_tono', 10)->default('verde');
            $table->boolean('destacada')->default(false);
            $table->foreignId('imagen_id')->nullable()->constrained('landing_imagenes')->nullOnDelete();
            $table->date('vigente_desde')->nullable();
            $table->date('vigente_hasta')->nullable();
            $table->unsignedInteger('cupos_restantes')->nullable();
            $table->string('mensaje_whatsapp', 300)->nullable();
            $table->boolean('activa')->default(true);
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });

        // Blog y galeria: "articulo" tiene pagina propia, "foto" se abre ampliada.
        Schema::create('landing_publicaciones', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 10)->default('articulo');
            $table->string('titulo', 120);
            $table->string('slug', 140)->unique();
            $table->string('resumen', 300)->nullable();
            $table->text('contenido')->nullable();
            $table->foreignId('imagen_id')->nullable()->constrained('landing_imagenes')->nullOnDelete();
            $table->boolean('publicada')->default(true);
            $table->date('fecha_publicacion');
            $table->timestamps();

            $table->index(['publicada', 'fecha_publicacion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_publicaciones');
        Schema::dropIfExists('landing_promociones');
        Schema::dropIfExists('landing_imagenes');
    }
};
