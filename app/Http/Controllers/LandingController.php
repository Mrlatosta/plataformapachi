<?php

namespace App\Http\Controllers;

use App\Models\Estudio;
use App\Models\LandingImagen;
use App\Models\LandingPromocion;
use App\Models\LandingPublicacion;
use App\Support\TextoPublico;
use Illuminate\Http\Request;

/**
 * Pagina publica para pacientes. Solo expone estudios marcados como visibles
 * y nunca el costo interno.
 */
class LandingController extends Controller
{
    public function inicio()
    {
        $limite = (int) config('landing.publicaciones_inicio');
        $publicaciones = LandingPublicacion::publicadas()
            ->with(LandingImagen::COLUMNAS_LIGERAS)
            ->limit($limite + 1)
            ->get();

        return view('landing.inicio', [
            'estudios' => $this->estudiosVisibles(),
            'promociones' => $this->promocionesPublicas(),
            'publicaciones' => $publicaciones->take($limite),
            'hayMasPublicaciones' => $publicaciones->count() > $limite,
        ]);
    }

    public function novedades()
    {
        return view('landing.novedades', [
            'publicaciones' => LandingPublicacion::publicadas()
                ->with(LandingImagen::COLUMNAS_LIGERAS)
                ->paginate((int) config('landing.publicaciones_por_pagina')),
        ]);
    }

    public function articulo(string $slug)
    {
        $publicacion = LandingPublicacion::publicadas()
            ->where('tipo', 'articulo')
            ->where('slug', $slug)
            ->with(LandingImagen::COLUMNAS_LIGERAS)
            ->firstOrFail();

        return view('landing.articulo', [
            'publicacion' => $publicacion,
            'relacionadas' => LandingPublicacion::publicadas()
                ->whereKeyNot($publicacion->id)
                ->with(LandingImagen::COLUMNAS_LIGERAS)
                ->limit(3)
                ->get(),
        ]);
    }

    /** Mismo formato que usa la vista; util para una app o CMS externo. */
    public function promociones()
    {
        return response()
            ->json(['moneda' => 'MXN', 'ofertas' => $this->promocionesPublicas()])
            ->header('Cache-Control', 'public, max-age=60');
    }

    public function imagen(Request $request, string $uuid)
    {
        $imagen = LandingImagen::where('uuid', $uuid)->firstOrFail(['uuid', 'mime', 'datos']);

        // El uuid cambia cada vez que se sube una imagen nueva: se puede cachear para siempre
        $respuesta = response('', 200, [
            'Content-Type' => $imagen->mime,
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ])->setEtag($imagen->uuid);

        if ($respuesta->isNotModified($request)) {
            return $respuesta;
        }

        return $respuesta->setContent(base64_decode($imagen->datos));
    }

    private function estudiosVisibles()
    {
        return Estudio::visiblesPublico()
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'tipo_muestra', 'tiempo_entrega', 'precio'])
            ->map(function (Estudio $estudio) {
                $nombre = TextoPublico::nombre($estudio->nombre);
                $muestra = $estudio->tipo_muestra ? TextoPublico::nombre($estudio->tipo_muestra) : null;
                $precio = (float) $estudio->precio;

                return [
                    'nombre' => $nombre,
                    'muestra' => $muestra,
                    'entrega' => $estudio->tiempo_entrega ? trim($estudio->tiempo_entrega) : null,
                    'precio' => $precio > 0 ? TextoPublico::precio($precio) : null,
                    'buscar' => TextoPublico::paraBuscar($nombre, (string) $muestra),
                ];
            });
    }

    private function promocionesPublicas()
    {
        return LandingPromocion::publicables()
            ->with(LandingImagen::COLUMNAS_LIGERAS)
            ->get()
            ->map->paraPublico()
            ->values();
    }
}
