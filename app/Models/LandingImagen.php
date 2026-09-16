<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingImagen extends Model
{
    protected $table = 'landing_imagenes';

    protected $fillable = ['uuid', 'mime', 'ancho', 'alto', 'tamano', 'alt', 'datos'];

    // El binario nunca debe viajar en listados ni respuestas JSON
    protected $hidden = ['datos'];

    // Columnas para cargar la relacion sin el binario
    public const COLUMNAS_LIGERAS = 'imagen:id,uuid,ancho,alto,alt';

    public function url(): string
    {
        return route('landing.imagen', $this->uuid);
    }
}
