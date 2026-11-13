<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Si el administrador desactiva un usuario, su sesion abierta se cierra en la
 * siguiente peticion (no tiene que esperar a que caduque).
 */
class CerrarSesionInactivos
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario && $usuario->activo === false) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Tu usuario fue desactivado.'], 401);
            }

            return redirect()->route('login')->withErrors(['email' => 'Tu usuario fue desactivado. Habla con el administrador.']);
        }

        return $next($request);
    }
}
