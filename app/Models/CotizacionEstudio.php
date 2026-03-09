<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CotizacionEstudio extends Model
{
    protected $table = 'cotizacion_estudios';

    protected $fillable = [
        'cotizacion_id',
        'estudio_id',
        'precio',
        'cantidad',
        'descripcion',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
    ];

    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class);
    }

    public function estudio()
    {
        return $this->belongsTo(Estudio::class);
    }
}
