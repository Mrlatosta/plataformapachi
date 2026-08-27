<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estudio extends Model
{
    protected $fillable = ['nombre', 'leyenda', 'tipo_muestra', 'metodo', 'tiempo_entrega', 'precio', 'costo'];

    protected $casts = [
        'precio' => 'decimal:2',
        'costo' => 'decimal:2',
    ];

    public function examenes()
    {
        return $this->hasMany(Examen::class);
    }
}
