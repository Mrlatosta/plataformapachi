<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * T6: restringe una ruta a ciertos roles. Uso: ->middleware('rol:admin').
 * Las opciones se ocultan tambien en el menu, pero la seguridad real esta aqui:
 * llamar la URL directo devuelve 403.
 */
class EnsureRol
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $usuario = $request->user();
        $rol = $usuario?->rol ?: 'admin';

        if (! $usuario || ! in_array($rol, $roles, true)) {
            $mensaje = 'Tu usuario no tiene permiso para esta opción.';

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => $mensaje], 403);
            }

            abort(403, $mensaje);
        }

        return $next($request);
    }
}
