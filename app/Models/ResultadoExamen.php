<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResultadoExamen extends Model {
    protected $table = 'resultado_examen'; // ← asegúrate de tener esto
protected $fillable = ['reporte_estudio_id', 'examen_id', 'resultado', 'fuera_rango'];

  protected $casts = [
        'fuera_rango' => 'boolean',
    ];

    public function reporteEstudio() {
        return $this->belongsTo(ReporteEstudio::class);
    }

    public function examen() {
        return $this->belongsTo(Examen::class);
    }
}