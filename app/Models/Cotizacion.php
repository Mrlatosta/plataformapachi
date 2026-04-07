<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cotizacion extends Model
{
    protected $table = 'cotizaciones';

    protected $fillable = [
        'folio',
        'paciente_id',
        'nombre_cliente',
        'email',
        'telefono',
        'direccion',
        'fecha_cotizacion',
        'vigencia',
        'descuento',
        'subtotal',
        'total',
        'notas',
        'estado',
    ];

    protected $casts = [
        'fecha_cotizacion' => 'date',
        'descuento' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class);
    }

    public function estudios()
    {
        return $this->hasMany(CotizacionEstudio::class);
    }

    /**
     * Genera un folio único para la cotización
     */
    public static function generarFolio(): string
    {
        $año = date('Y');

        // Evita depender de columnas de fecha y reduce colisiones en solicitudes concurrentes.
        $base = static::where('folio', 'like', "COT-{$año}-%")->count() + 1;

        for ($intento = 0; $intento < 10; $intento++) {
            $numero = str_pad($base + $intento, 4, '0', STR_PAD_LEFT);
            $folio = "COT-{$año}-{$numero}";

            if (!static::where('folio', $folio)->exists()) {
                return $folio;
            }
        }

        return 'COT-' . $año . '-' . strtoupper(substr(uniqid(), -6));
    }
}
