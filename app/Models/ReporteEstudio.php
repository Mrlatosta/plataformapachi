<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReporteEstudio extends Model
{
    /** Sin resultados capturados todavia (p. ej. urocultivo que sale en 72 h). */
    public const PENDIENTE = 'pendiente';
    /** Con resultados, esperando que el Q.F.B. los valide. */
    public const CAPTURADO = 'capturado';
    public const VALIDADO = 'validado';
    /** Validado y ya enviado o descargado para el paciente. */
    public const ENTREGADO = 'entregado';

    public const ESTADOS = [self::PENDIENTE, self::CAPTURADO, self::VALIDADO, self::ENTREGADO];

    protected $table = 'reporte_estudio';

    protected $fillable = [
        'reporte_id', 'estudio_id', 'orden', 'elaboro', 'valido', 'tipo_muestra', 'metodo', 'precio', 'observaciones',
        'incluir_en_reporte', 'estado', 'capturado_por', 'capturado_at', 'validado_por', 'validado_at', 'entregado_at',
        'muestra_metodo_cambiado_por', 'muestra_metodo_cambiado_at',
    ];

    protected $casts = [
        'incluir_en_reporte' => 'boolean',
        'capturado_at' => 'datetime',
        'validado_at' => 'datetime',
        'entregado_at' => 'datetime',
        'muestra_metodo_cambiado_at' => 'datetime',
    ];

    public function reporte()
    {
        return $this->belongsTo(Reporte::class);
    }

    public function estudio()
    {
        return $this->belongsTo(Estudio::class);
    }

    public function resultados()
    {
        // Orden de captura; sin esto Postgres los regresa revueltos tras editarlos
        return $this->hasMany(ResultadoExamen::class)->orderBy('id');
    }

    public function capturadoPor()
    {
        return $this->belongsTo(User::class, 'capturado_por');
    }

    public function validadoPor()
    {
        return $this->belongsTo(User::class, 'validado_por');
    }

    /** T13: quien cambio el tipo de muestra o el metodo de esta orden. */
    public function muestraMetodoCambiadoPor()
    {
        return $this->belongsTo(User::class, 'muestra_metodo_cambiado_por');
    }

    public function estaValidado(): bool
    {
        return in_array($this->estado, [self::VALIDADO, self::ENTREGADO], true);
    }

    /**
     * Pendiente o capturado segun haya algun resultado escrito
     * (un '*' tambien cuenta: es la marca para no imprimir el examen).
     */
    public static function estadoSinValidar(iterable $resultados): string
    {
        foreach ($resultados as $resultado) {
            $valor = is_array($resultado) ? ($resultado['resultado'] ?? '') : $resultado->resultado;
            if (trim((string) $valor) !== '') {
                return self::CAPTURADO;
            }
        }

        return self::PENDIENTE;
    }

    public function examenes()
    {
        return $this->belongsToMany(Examen::class, 'resultado_examen', 'reporte_estudio_id', 'examen_id')
            ->withPivot('resultado')
            ->withTimestamps();
    }
}
