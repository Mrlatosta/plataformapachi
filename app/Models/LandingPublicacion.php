<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class LandingPublicacion extends Model
{
    protected $table = 'landing_publicaciones';

    public const TIPOS = ['articulo', 'foto'];

    protected $fillable = [
        'tipo', 'titulo', 'slug', 'resumen', 'contenido', 'imagen_id', 'publicada', 'fecha_publicacion',
    ];

    protected $casts = [
        'publicada' => 'boolean',
        'fecha_publicacion' => 'date:Y-m-d',
    ];

    public function imagen(): BelongsTo
    {
        return $this->belongsTo(LandingImagen::class, 'imagen_id');
    }

    public function scopePublicadas(Builder $query): Builder
    {
        return $query->where('publicada', true)
            ->whereDate('fecha_publicacion', '<=', now(config('landing.zona_horaria'))->toDateString())
            ->orderByDesc('fecha_publicacion')
            ->orderByDesc('id');
    }

    public function esArticulo(): bool
    {
        return $this->tipo === 'articulo';
    }

    /** Parrafos separados por una linea en blanco; el texto se escapa en la vista. */
    public function parrafos(): array
    {
        return array_values(array_filter(
            array_map('trim', preg_split('/\R{2,}/u', (string) $this->contenido)),
            fn ($p) => $p !== ''
        ));
    }

    public function fechaLegible(): string
    {
        return $this->fecha_publicacion->locale('es')->translatedFormat('j \d\e F \d\e Y');
    }

    public static function slugUnico(string $titulo, ?int $ignorarId = null): string
    {
        $base = Str::limit(Str::slug($titulo), 120, '') ?: 'publicacion';
        $slug = $base;
        $n = 2;

        while (static::where('slug', $slug)->when($ignorarId, fn ($q) => $q->where('id', '!=', $ignorarId))->exists()) {
            $slug = "{$base}-{$n}";
            $n++;
        }

        return $slug;
    }
}
