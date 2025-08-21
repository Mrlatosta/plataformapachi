<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ReporteEstudio;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Reporte extends Model
{
    
    protected $fillable = [
    'folio',
    'nombre_cliente',
    'email',
    'fecha_nacimiento',
    'edad',
    'sexo',
    'toma_muestra',
    'fecha_reporte',
    'fecha_validacion',
    'medico_solicitante',
];

    public function estudios()
    {
        return $this->hasMany(ReporteEstudio::class);
    }
}
