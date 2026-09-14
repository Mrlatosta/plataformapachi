<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ReporteEstudio;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;


class Reporte extends Model
{
    /**
     * Cada reporte nace con un token aleatorio para su QR de verificacion.
     */
    protected static function booted(): void
    {
        static::creating(function (Reporte $reporte) {
            $reporte->token_verificacion ??= Str::random(40);
        });
    }

    // El token da acceso publico al PDF: no se expone en respuestas JSON
    protected $hidden = [
        'token_verificacion',
    ];

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
    'descuento',
];

    protected $casts = [
        'aplica_iva' => 'boolean',
        'descuento' => 'float',
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
     * Monto del descuento (porcentaje sobre el subtotal).
     */
    public function getMontoDescuentoAttribute(): float
    {
        return round($this->subtotal * ((float) $this->descuento / 100), 2);
    }

    /**
     * Subtotal con el descuento aplicado: base sobre la que se calcula el IVA.
     * Mismo calculo que CotizacionController::store.
     */
    public function getBaseGravableAttribute(): float
    {
        return $this->subtotal - $this->subtotal * ((float) $this->descuento / 100);
    }

    /**
     * Monto de IVA calculado sobre el subtotal con descuento.
     */
    public function getMontoIvaAttribute(): float
    {
        return round($this->base_gravable * ($this->porcentaje_iva / 100), 2);
    }

    /**
     * Total a pagar (subtotal - descuento + IVA).
     */
    public function getTotalAttribute(): float
    {
        return round($this->base_gravable + $this->monto_iva, 2);
    }
}
