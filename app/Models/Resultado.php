<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resultado extends Model
{
    public function up()
{
    Schema::create('resultados', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('estudio_id');
        $table->unsignedBigInteger('examen_id');
        $table->string('cliente'); // Nombre del cliente/paciente
        $table->string('resultado');
        $table->timestamps();

        $table->foreign('estudio_id')->references('id')->on('estudios')->onDelete('cascade');
        $table->foreign('examen_id')->references('id')->on('examenes')->onDelete('cascade');
    });
}

}
