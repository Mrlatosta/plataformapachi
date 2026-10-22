<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jornada extends Model
{
    protected $fillable = [
        'nombre', 'empresa', 'fecha_toma', 'medico_id', 'medico_solicitante',
        'aplica_iva', 'estudios', 'notas', 'creado_por',
    ];

    protected $casts = [
        'fecha_toma' => 'datetime',
        'aplica_iva' => 'boolean',
        'estudios' => 'array',
    ];

    public function participantes(): HasMany
    {
        return $this->hasMany(JornadaParticipante::class);
    }

    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class);
    }
}
