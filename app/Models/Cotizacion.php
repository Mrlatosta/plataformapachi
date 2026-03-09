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
        $ultimo = static::whereYear('created_at', $año)->count();
        $numero = str_pad($ultimo + 1, 4, '0', STR_PAD_LEFT);
        return "COT-{$año}-{$numero}";
    }
}
