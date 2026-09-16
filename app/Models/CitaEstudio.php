<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CitaEstudio extends Model
{
    protected $table = 'cita_estudios';

    protected $fillable = ['cita_id', 'estudio_id', 'nombre', 'precio'];

    protected $casts = [
        'precio' => 'decimal:2',
    ];

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class);
    }

    public function estudio(): BelongsTo
    {
        return $this->belongsTo(Estudio::class);
    }
}
