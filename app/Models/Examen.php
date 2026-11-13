<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Examen extends Model
{
    protected $table = 'examenes';

    protected $fillable = ['estudio_id', 'seccion', 'orden', 'nombre_examen', 'unidad', 'valor_referencia'];

    public function estudio()
    {
        return $this->belongsTo(Estudio::class);
    }

    public function resultados()
    {
        return $this->hasMany(ResultadoExamen::class);
    }

    /** T5: tabla de niveles de referencia, en el orden definido en el catalogo. */
    public function niveles()
    {
        return $this->hasMany(ExamenNivel::class)->orderBy('orden')->orderBy('id');
    }
}
