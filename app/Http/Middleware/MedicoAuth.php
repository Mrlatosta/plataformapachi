<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MedicoAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!session('medico_id')) {
            return redirect()->route('medico.login');
        }

        return $next($request);
    }
}
