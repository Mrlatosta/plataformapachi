<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * T12: comision del medico que refirio una orden.
 */
class Comision extends Model
{
    public const PENDIENTE = 'pendiente';
    public const PAGADA = 'pagada';

    protected $table = 'comisiones';

    protected $fillable = [
        'reporte_id', 'medico_id', 'monto_base', 'porcentaje', 'comision', 'estado', 'pagada_at', 'pagada_por',
    ];

    protected $casts = [
        'monto_base' => 'decimal:2',
        'porcentaje' => 'decimal:2',
        'comision' => 'decimal:2',
        'pagada_at' => 'datetime',
    ];

    public function reporte()
    {
        return $this->belongsTo(Reporte::class);
    }

    public function medico()
    {
        return $this->belongsTo(Medico::class);
    }

    public function pagadaPor()
    {
        return $this->belongsTo(User::class, 'pagada_por');
    }
}
