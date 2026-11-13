<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Un renglon de la tabla de niveles de referencia de un examen (T5):
 * "0.0 - 10.0 Normal", "10.0 - 20.0 Elevado"...
 */
class ExamenNivel extends Model
{
    protected $table = 'examen_niveles';

    protected $fillable = ['examen_id', 'orden', 'desde', 'hasta', 'interpretacion', 'es_normal'];

    protected $casts = [
        'desde' => 'float',
        'hasta' => 'float',
        'es_normal' => 'boolean',
        'orden' => 'integer',
    ];

    public function examen()
    {
        return $this->belongsTo(Examen::class);
    }

    public function contiene(?float $valor): bool
    {
        if ($valor === null) {
            return false;
        }

        return ($this->desde === null || $valor >= $this->desde)
            && ($this->hasta === null || $valor <= $this->hasta);
    }

    /**
     * "0.0 – 10.0", "< 10.0" o "> 20.0", con los decimales que se capturaron.
     */
    public function intervaloTexto(): string
    {
        $formato = fn (float $n) => rtrim(rtrim(number_format($n, 4, '.', ''), '0'), '.') . (fmod($n, 1) == 0.0 ? '.0' : '');

        return match (true) {
            $this->desde !== null && $this->hasta !== null => $formato($this->desde) . ' – ' . $formato($this->hasta),
            $this->desde !== null => '≥ ' . $formato($this->desde),
            $this->hasta !== null => '≤ ' . $formato($this->hasta),
            default => '—',
        };
    }
}
