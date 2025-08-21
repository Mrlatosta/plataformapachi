<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estudio extends Model
{
protected $fillable = ['nombre', 'leyenda', 'tipo_muestra', 'metodo'];

    public function examenes()
    {
        return $this->hasMany(Examen::class);
    }
}
