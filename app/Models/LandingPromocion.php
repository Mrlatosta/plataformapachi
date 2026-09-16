<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandingPromocion extends Model
{
    protected $table = 'landing_promociones';

    public const TONOS = ['verde', 'rosa', 'marino'];

    // Ilustracion que se usa cuando la promocion no tiene imagen propia
    public const IMAGEN_DEFAULT = '/landing/img/ofertas/checkup.svg';

    protected $fillable = [
        'titulo', 'descripcion', 'incluye', 'precio_anterior', 'precio_actual',
        'etiqueta', 'etiqueta_tono', 'destacada', 'imagen_id', 'vigente_desde',
        'vigente_hasta', 'cupos_restantes', 'mensaje_whatsapp', 'activa', 'orden',
    ];

    protected $casts = [
        'incluye' => 'array',
        'precio_anterior' => 'decimal:2',
        'precio_actual' => 'decimal:2',
        'destacada' => 'boolean',
        'activa' => 'boolean',
        'vigente_desde' => 'date:Y-m-d',
        'vigente_hasta' => 'date:Y-m-d',
        'cupos_restantes' => 'integer',
        'orden' => 'integer',
    ];

    public function imagen(): BelongsTo
    {
        return $this->belongsTo(LandingImagen::class, 'imagen_id');
    }

    /**
     * Promociones que pueden mostrarse hoy (hora de Cancun). La vigencia exacta
     * por hora se vuelve a revisar en el navegador.
     */
    public function scopePublicables(Builder $query): Builder
    {
        $hoy = now(config('landing.zona_horaria'))->toDateString();

        return $query->where('activa', true)
            ->where(fn ($q) => $q->whereNull('vigente_desde')->orWhereDate('vigente_desde', '<=', $hoy))
            ->where(fn ($q) => $q->whereNull('vigente_hasta')->orWhereDate('vigente_hasta', '>=', $hoy))
            ->where(fn ($q) => $q->whereNull('cupos_restantes')->orWhere('cupos_restantes', '>', 0))
            ->orderBy('orden')
            ->orderBy('id');
    }

    /** Forma que consume public/landing/js/landing.js */
    public function paraPublico(): array
    {
        $zona = config('landing.zona_horaria');
        $precio = (float) $this->precio_actual;

        return [
            'id' => 'promo-' . $this->id,
            'activa' => $this->activa,
            'titulo' => $this->titulo,
            'descripcion' => $this->descripcion,
            'incluye' => array_values($this->incluye ?? []),
            'precioAnterior' => $this->precio_anterior !== null ? (float) $this->precio_anterior : null,
            'precioActual' => $precio,
            'etiqueta' => $this->etiqueta ? ['texto' => $this->etiqueta, 'tono' => $this->etiqueta_tono] : null,
            'destacada' => $this->destacada,
            'imagen' => $this->imagen
                ? ['src' => $this->imagen->url(), 'alt' => $this->imagen->alt ?? '', 'ancho' => $this->imagen->ancho, 'alto' => $this->imagen->alto]
                : ['src' => self::IMAGEN_DEFAULT, 'alt' => '', 'ancho' => 480, 'alto' => 300],
            'vigenteDesde' => $this->vigente_desde?->copy()->shiftTimezone($zona)->startOfDay()->toIso8601String(),
            'vigenteHasta' => $this->vigente_hasta?->copy()->shiftTimezone($zona)->endOfDay()->toIso8601String(),
            'cuposRestantes' => $this->cupos_restantes,
            'mensajeWhatsApp' => $this->mensaje_whatsapp
                ?: sprintf('Hola, me interesa la promoción %s de $%s.', $this->titulo, number_format($precio, 0)),
        ];
    }
}
