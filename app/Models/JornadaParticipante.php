<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JornadaParticipante extends Model
{
    public const PENDIENTE = 'pendiente';
    public const LISTO = 'listo';

    protected $table = 'jornada_participantes';

    protected $fillable = [
        'jornada_id', 'nombre', 'sexo', 'fecha_nacimiento', 'edad',
        'resultados', 'estado', 'capturado_en', 'reporte_id',
    ];

    // Igual que el default de la tabla, para que el modelo recién creado ya lo traiga
    protected $attributes = [
        'estado' => self::PENDIENTE,
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date:Y-m-d',
        'edad' => 'integer',
        'resultados' => 'array',
        'capturado_en' => 'datetime',
    ];

    public function jornada(): BelongsTo
    {
        return $this->belongsTo(Jornada::class);
    }

    public function reporte(): BelongsTo
    {
        return $this->belongsTo(Reporte::class);
    }

    /**
     * Lo que falta para poder emitir su reporte (vacío si está completo).
     */
    public function datosFaltantes(): array
    {
        $faltan = [];
        if (! $this->sexo) {
            $faltan[] = 'sexo';
        }
        if (! $this->fecha_nacimiento && $this->edad === null) {
            $faltan[] = 'edad o fecha de nacimiento';
        }

        return $faltan;
    }
}
