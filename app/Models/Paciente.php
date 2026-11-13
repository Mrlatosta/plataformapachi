<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Paciente extends Model
{
    protected $fillable = [
        'nombre',
        'email',
        'telefono',
        'fecha_nacimiento',
        'edad',
        'sexo',
        'direccion',
        'notas',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
    ];

    protected $hidden = [
        'nombre_busqueda',
    ];

    protected static function booted(): void
    {
        static::saving(function (Paciente $paciente) {
            $paciente->nombre = trim(preg_replace('/\s+/u', ' ', (string) $paciente->nombre));
            $paciente->nombre_busqueda = self::normalizarNombre($paciente->nombre);
        });
    }

    /**
     * "  José  PÉREZ " -> "jose perez": sirve para buscar y para detectar duplicados
     * aunque se capture con o sin acentos o mayusculas.
     */
    public static function normalizarNombre(?string $nombre): string
    {
        $texto = Str::lower(Str::ascii((string) $nombre));

        return trim(preg_replace('/\s+/', ' ', preg_replace('/[^a-z0-9 ]/', ' ', $texto)));
    }

    /**
     * Paciente con el mismo nombre (normalizado) y fecha de nacimiento.
     */
    public static function buscarDuplicado(?string $nombre, $fechaNacimiento, ?int $excluirId = null): ?self
    {
        $normalizado = self::normalizarNombre($nombre);
        if ($normalizado === '') {
            return null;
        }

        return static::where('nombre_busqueda', $normalizado)
            ->when(
                $fechaNacimiento,
                fn (Builder $q) => $q->whereDate('fecha_nacimiento', substr((string) $fechaNacimiento, 0, 10)),
                fn (Builder $q) => $q->whereNull('fecha_nacimiento'),
            )
            ->when($excluirId, fn (Builder $q) => $q->whereKeyNot($excluirId))
            ->first();
    }

    /**
     * Cada palabra escrita debe aparecer en el nombre: "lara ray" encuentra "Raymundo Lara".
     */
    public function scopeBuscar(Builder $query, string $termino): Builder
    {
        foreach (explode(' ', self::normalizarNombre($termino)) as $palabra) {
            if ($palabra !== '') {
                $query->where('nombre_busqueda', 'like', '%' . $palabra . '%');
            }
        }

        return $query;
    }

    public function reportes()
    {
        return $this->hasMany(Reporte::class);
    }
}
