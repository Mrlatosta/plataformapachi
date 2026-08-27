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
    'medico_id',
    'aplica_iva',
];

    protected $casts = [
        'aplica_iva' => 'boolean',
    ];

    public function estudios()
    {
        return $this->hasMany(ReporteEstudio::class)->orderBy('orden')->orderBy('id');
    }

    public function medico()
    {
        return $this->belongsTo(Medico::class);
    }

    /**
     * Suma de los precios de los estudios del reporte (sin IVA).
     */
    public function getSubtotalAttribute(): float
    {
        return round((float) $this->estudios->sum('precio'), 2);
    }

    /**
     * Porcentaje de IVA aplicado al reporte (0 si la casilla esta desactivada).
     */
    public function getPorcentajeIvaAttribute(): float
    {
        return $this->aplica_iva ? (float) config('facturacion.iva') : 0.0;
    }

    /**
     * Monto de IVA calculado sobre el subtotal.
     */
    public function getMontoIvaAttribute(): float
    {
        return round($this->subtotal * ($this->porcentaje_iva / 100), 2);
    }

    /**
     * Total a pagar (subtotal + IVA).
     */
    public function getTotalAttribute(): float
    {
        return round($this->subtotal + $this->monto_iva, 2);
    }
}
