<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ReporteEstudio;
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
    'paciente_id',
    'creado_por',
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

    /**
     * Folio que le toca al siguiente reporte: RPT-0001, RPT-0002...
     */
    public static function siguienteFolio(): string
    {
        $ultimoFolio = static::orderBy('id', 'desc')->value('folio');
        $numero = $ultimoFolio ? (int) preg_replace('/\D/', '', $ultimoFolio) + 1 : 1;

        return 'RPT-' . str_pad($numero, 4, '0', STR_PAD_LEFT);
    }

    public function estudios()
    {
        return $this->hasMany(ReporteEstudio::class)->orderBy('orden')->orderBy('id');
    }

    public function medico()
    {
        return $this->belongsTo(Medico::class);
    }

    /** T12: comision del medico que refirio la orden. */
    public function comision()
    {
        return $this->hasOne(Comision::class);
    }

    public function paciente()
    {
        return $this->belongsTo(Paciente::class);
    }

    /**
     * Nombre del PDF descargado: "Raymundo Lara Sandoval - RPT-0155.pdf".
     * Con sufijo: "Raymundo Lara Sandoval - RPT-0155 - Orden de trabajo.pdf".
     */
    public function nombreArchivo(?string $sufijo = null): string
    {
        // Quita caracteres que Windows/macOS no aceptan en nombres de archivo
        $nombre = preg_replace('/[\\\\\/:*?"<>|\x00-\x1F]+/u', ' ', (string) $this->nombre_cliente);
        $nombre = trim(preg_replace('/\s+/u', ' ', $nombre));
        $nombre = mb_substr(mb_convert_case(mb_strtolower($nombre), MB_CASE_TITLE), 0, 80);

        $partes = array_filter([$nombre ?: 'Reporte', $this->folio, $sufijo]);

        return implode(' - ', $partes) . '.pdf';
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
