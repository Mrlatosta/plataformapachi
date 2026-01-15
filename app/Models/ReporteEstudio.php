<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Estudio;
use App\Models\Examen;
use App\Models\ResultadoExamen;
use App\Models\Reporte;




class ReporteEstudio extends Model {
    protected $table = 'reporte_estudio';
    protected $fillable = ['reporte_id', 'estudio_id', 'elaboro', 'valido', 'tipo_muestra', 'metodo', 'precio', 'observaciones'];

    public function reporte() {
        return $this->belongsTo(Reporte::class);
    }

    public function estudio() {
        return $this->belongsTo(Estudio::class);
    }

    public function resultados() {
        return $this->hasMany(ResultadoExamen::class);
    }

    public function examenes()
    {
        return $this->belongsToMany(
                    Examen::class,          // Modelo relacionado
                    'resultado_examen',     // Tabla pivote
                    'reporte_estudio_id',   // FK local en pivote
                    'examen_id'             // FK relacionada en pivote
                )
                ->withPivot('resultado')   // Trae la columna resultado
                ->withTimestamps();
    }
}
