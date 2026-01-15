<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResultadoExamen extends Model {
    protected $table = 'resultado_examen'; // ← asegúrate de tener esto
protected $fillable = ['reporte_estudio_id', 'examen_id', 'resultado', 'fuera_rango'];

  protected $casts = [
        'fuera_rango' => 'boolean',
    ];

    // Accessor para asegurar que fuera_rango siempre retorne un booleano válido
    public function getFueraRangoAttribute($value)
    {
        // Convertir explícitamente a booleano
        // Maneja casos: null, 0, 1, "0", "1", true, false, "true", "false"
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
    }

    public function reporteEstudio() {
        return $this->belongsTo(ReporteEstudio::class);
    }

    public function examen() {
        return $this->belongsTo(Examen::class);
    }
}