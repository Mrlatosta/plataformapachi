<?php

namespace App\Http\Controllers;

use App\Models\Estudio;
use App\Models\LandingImagen;
use App\Models\LandingPromocion;
use App\Models\LandingPublicacion;
use App\Services\LandingImagenes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Panel "Gestión Landing": promociones y blog/galeria de /inicio.
 * Los estudios visibles se administran desde Gestión de Estudios.
 */
class GestionLandingController extends Controller
{
    private const MENSAJES = [
        'required' => 'Este campo es obligatorio.',
        'titulo.max' => 'El título puede tener hasta :max caracteres; acórtalo un poco.',
        'descripcion.max' => 'La descripción puede tener hasta :max caracteres.',
        'resumen.max' => 'El resumen puede tener hasta :max caracteres.',
        'precio_actual.min' => 'El precio de promoción debe ser mayor a $0.',
        'precio_actual.numeric' => 'Escribe solo números, por ejemplo 449.',
        'precio_anterior.numeric' => 'Escribe solo números, por ejemplo 610.',
        'precio_anterior.gt' => 'El precio normal debe ser mayor al de promoción; si no hay descuento, déjalo vacío.',
        'vigente_hasta.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la de inicio.',
        'cupos_restantes.integer' => 'Escribe un número entero de lugares, o déjalo vacío si no hay límite.',
        'contenido.required_if' => 'Un artículo necesita texto. Si solo quieres mostrar una foto, elige el tipo "Foto de galería".',
        'imagen.required' => 'Una foto de galería necesita una imagen.',
        'imagen.image' => 'El archivo debe ser una imagen JPG, PNG o WebP.',
        'imagen.mimes' => 'El archivo debe ser una imagen JPG, PNG o WebP.',
        'imagen.max' => 'La imagen pesa más de 4 MB; usa una más ligera.',
        'imagen.uploaded' => 'La imagen no se pudo subir, probablemente por su tamaño. Usa una más ligera.',
    ];

    public function __construct(private LandingImagenes $imagenes)
    {
    }

    public function page()
    {
        return Inertia::render('GestionLanding', [
            'estudiosVisibles' => Estudio::visiblesPublico()->count(),
            'estudiosTotal' => Estudio::count(),
        ]);
    }

    // ---------- Promociones ----------

    public function promociones()
    {
        return LandingPromocion::with(LandingImagen::COLUMNAS_LIGERAS)
            ->orderBy('orden')
            ->orderByDesc('id')
            ->get()
            ->map(fn (LandingPromocion $p) => $this->jsonConImagen($p, [
                'incluye_texto' => implode("\n", $p->incluye ?? []),
            ]));
    }

    public function guardarPromocion(Request $request, ?int $id = null)
    {
        $promocion = $id ? LandingPromocion::findOrFail($id) : new LandingPromocion();

        $validado = $request->validate([
            'titulo' => 'required|string|max:120',
            'descripcion' => 'nullable|string|max:400',
            'incluye' => 'nullable|string|max:1500',
            'precio_actual' => 'required|numeric|min:1|max:999999',
            'precio_anterior' => 'nullable|numeric|gt:precio_actual|max:999999',
            'etiqueta' => 'nullable|string|max:30',
            'etiqueta_tono' => ['required', Rule::in(LandingPromocion::TONOS)],
            'destacada' => 'boolean',
            'activa' => 'boolean',
            'vigente_desde' => 'nullable|date',
            'vigente_hasta' => array_filter(['nullable', 'date', $request->filled('vigente_desde') ? 'after_or_equal:vigente_desde' : null]),
            'cupos_restantes' => 'nullable|integer|min:0|max:100000',
            'mensaje_whatsapp' => 'nullable|string|max:300',
            'orden' => 'nullable|integer|min:0|max:9999',
            ...$this->reglasImagen(),
        ], self::MENSAJES);

        $datos = [
            'titulo' => trim($validado['titulo']),
            'descripcion' => $validado['descripcion'] ?? null,
            'incluye' => $this->lineas($validado['incluye'] ?? null),
            'precio_actual' => $validado['precio_actual'],
            'precio_anterior' => $validado['precio_anterior'] ?? null,
            'etiqueta' => $validado['etiqueta'] ?? null,
            'etiqueta_tono' => $validado['etiqueta_tono'],
            'destacada' => $request->boolean('destacada'),
            'activa' => $request->boolean('activa'),
            'vigente_desde' => $validado['vigente_desde'] ?? null,
            'vigente_hasta' => $validado['vigente_hasta'] ?? null,
            'cupos_restantes' => $validado['cupos_restantes'] ?? null,
            'mensaje_whatsapp' => $validado['mensaje_whatsapp'] ?? null,
            'orden' => $validado['orden'] ?? 0,
        ];

        DB::transaction(function () use ($request, $promocion, $datos) {
            $this->guardarConImagen($request, $promocion, $datos);

            // Solo una promoción destacada a la vez: ancla la comparación de precios
            if ($promocion->destacada) {
                LandingPromocion::whereKeyNot($promocion->id)->update(['destacada' => false]);
            }
        });

        return response()->json(['message' => 'Promoción guardada'], $id ? 200 : 201);
    }

    public function cambiarEstadoPromocion(Request $request, int $id)
    {
        $validado = $request->validate(['activa' => 'required|boolean']);
        LandingPromocion::findOrFail($id)->update($validado);

        return response()->json(['message' => 'Estado actualizado']);
    }

    public function eliminarPromocion(int $id)
    {
        $this->eliminarConImagen(LandingPromocion::findOrFail($id));

        return response()->json(['message' => 'Promoción eliminada']);
    }

    // ---------- Blog y galería ----------

    public function publicaciones()
    {
        return LandingPublicacion::with(LandingImagen::COLUMNAS_LIGERAS)
            ->orderByDesc('fecha_publicacion')
            ->orderByDesc('id')
            ->get()
            ->map(fn (LandingPublicacion $p) => $this->jsonConImagen($p, [
                'url_publica' => $p->esArticulo() ? route('novedades.show', $p->slug) : null,
            ]));
    }

    public function guardarPublicacion(Request $request, ?int $id = null)
    {
        $publicacion = $id ? LandingPublicacion::findOrFail($id) : new LandingPublicacion();
        $quedaSinImagen = ! $publicacion->imagen_id || $request->boolean('quitar_imagen');

        $validado = $request->validate([
            'tipo' => ['required', Rule::in(LandingPublicacion::TIPOS)],
            'titulo' => 'required|string|max:120',
            'resumen' => 'nullable|string|max:300',
            'contenido' => 'nullable|string|max:20000|required_if:tipo,articulo',
            'publicada' => 'boolean',
            'fecha_publicacion' => 'nullable|date',
            ...$this->reglasImagen(requerida: $request->input('tipo') === 'foto' && $quedaSinImagen),
        ], self::MENSAJES);

        $titulo = trim($validado['titulo']);
        $datos = [
            'tipo' => $validado['tipo'],
            'titulo' => $titulo,
            'resumen' => $validado['resumen'] ?? null,
            'contenido' => $validado['tipo'] === 'articulo' ? ($validado['contenido'] ?? null) : null,
            'publicada' => $request->boolean('publicada'),
            'fecha_publicacion' => $validado['fecha_publicacion'] ?? now(config('landing.zona_horaria'))->toDateString(),
        ];

        // El slug se fija al crear para no romper enlaces ya compartidos
        if (! $publicacion->exists) {
            $datos['slug'] = LandingPublicacion::slugUnico($titulo);
        }

        DB::transaction(fn () => $this->guardarConImagen($request, $publicacion, $datos));

        return response()->json(['message' => 'Publicación guardada'], $id ? 200 : 201);
    }

    public function cambiarEstadoPublicacion(Request $request, int $id)
    {
        $validado = $request->validate(['publicada' => 'required|boolean']);
        LandingPublicacion::findOrFail($id)->update($validado);

        return response()->json(['message' => 'Estado actualizado']);
    }

    public function eliminarPublicacion(int $id)
    {
        $this->eliminarConImagen(LandingPublicacion::findOrFail($id));

        return response()->json(['message' => 'Publicación eliminada']);
    }

    // ---------- Apoyo ----------

    private function reglasImagen(bool $requerida = false): array
    {
        return [
            'imagen' => [$requerida ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'imagen_alt' => 'nullable|string|max:150',
            'quitar_imagen' => 'boolean',
        ];
    }

    /** Guarda el modelo y reemplaza, quita o actualiza su imagen. */
    private function guardarConImagen(Request $request, Model $modelo, array $datos): void
    {
        $anterior = $modelo->imagen_id;
        $alt = $request->input('imagen_alt');

        if ($request->hasFile('imagen')) {
            $datos['imagen_id'] = $this->imagenes->guardar($request->file('imagen'), $alt)->id;
        } elseif ($request->boolean('quitar_imagen')) {
            $datos['imagen_id'] = null;
        } elseif ($anterior && $request->has('imagen_alt')) {
            LandingImagen::whereKey($anterior)->update(['alt' => $alt ? Str::limit(trim($alt), 150, '') : null]);
        }

        $modelo->fill($datos)->save();

        if ($anterior && $anterior !== $modelo->imagen_id) {
            $this->imagenes->eliminar($anterior);
        }
    }

    private function eliminarConImagen(Model $modelo): void
    {
        DB::transaction(function () use ($modelo) {
            $imagenId = $modelo->imagen_id;
            $modelo->delete();
            $this->imagenes->eliminar($imagenId);
        });
    }

    private function jsonConImagen(Model $modelo, array $extra = []): array
    {
        return [
            ...$modelo->toArray(),
            'imagen_url' => $modelo->imagen?->url(),
            'imagen_alt' => $modelo->imagen?->alt,
            ...$extra,
        ];
    }

    private function lineas(?string $texto): array
    {
        return collect(preg_split('/\R/u', (string) $texto))
            // Quita viñetas que el usuario haya escrito ("- ", "• ", "* ")
            ->map(fn ($linea) => Str::limit(trim(preg_replace('/^\s*[-*\x{2022}]+\s*/u', '', $linea)), 120, ''))
            ->filter()
            ->take(12)
            ->values()
            ->all();
    }
}
