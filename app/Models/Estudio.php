<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Estudio extends Model
{
    protected $fillable = ['nombre', 'leyenda', 'tipo_muestra', 'metodo', 'tiempo_entrega', 'precio', 'costo', 'visible_publico'];

    protected $casts = [
        'precio' => 'decimal:2',
        'costo' => 'decimal:2',
        'visible_publico' => 'boolean',
    ];

    public function examenes()
    {
        return $this->hasMany(Examen::class);
    }

    /**
     * Estudios marcados para mostrarse a los pacientes en la pagina publica (/inicio).
     */
    public function scopeVisiblesPublico(Builder $query): Builder
    {
        return $query->where('visible_publico', true);
    }
}
