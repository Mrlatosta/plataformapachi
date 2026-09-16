<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cita extends Model
{
    protected $fillable = [
        'paciente_id', 'nombre', 'telefono', 'email', 'fecha', 'hora_inicio', 'hora_fin',
        'duracion_minutos', 'modalidad', 'direccion', 'estado', 'origen', 'indicaciones',
        'notas', 'total_estimado', 'creado_por',
    ];

    protected $casts = [
        'fecha' => 'date:Y-m-d',
        'duracion_minutos' => 'integer',
        'total_estimado' => 'decimal:2',
    ];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    public function estudios(): HasMany
    {
        // Sin orderBy aquí: Postgres no acepta ORDER BY en sum()/count() de la relación
        return $this->hasMany(CitaEstudio::class);
    }

    // Postgres devuelve "HH:MM:SS"; la agenda trabaja con "HH:MM"
    protected function horaInicio(): Attribute
    {
        return Attribute::get(fn ($valor) => $valor ? substr($valor, 0, 5) : null);
    }

    protected function horaFin(): Attribute
    {
        return Attribute::get(fn ($valor) => $valor ? substr($valor, 0, 5) : null);
    }
}
