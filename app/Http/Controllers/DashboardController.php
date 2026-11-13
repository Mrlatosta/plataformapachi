<?php

namespace App\Http\Controllers;

use App\Services\DashboardMetrics;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /** Segundos que se reutilizan las metricas de un mismo rango. */
    private const CACHE_SEGUNDOS = 300;

    public function index(Request $request)
    {
        // T6: Recepción no ve reportes de ventas; su pantalla de inicio es la captura
        if (! $request->user()->esAdmin()) {
            return redirect()->route('captura.resultados');
        }

        $request->validate([
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_fin' => ['nullable', 'date'],
            'refrescar' => ['nullable', 'boolean'],
        ]);

        // Las fechas del filtro son dias del calendario local del laboratorio
        $tz = config('app.local_timezone', 'America/Cancun');

        $fin = $request->filled('fecha_fin')
            ? Carbon::parse($request->input('fecha_fin'), $tz)->endOfDay()
            : Carbon::now($tz)->endOfDay();

        $inicio = $request->filled('fecha_inicio')
            ? Carbon::parse($request->input('fecha_inicio'), $tz)->startOfDay()
            : $fin->copy()->subDays(89)->startOfDay();

        if ($inicio->gt($fin)) {
            [$inicio, $fin] = [$fin->copy()->startOfDay(), $inicio->copy()->endOfDay()];
        }

        $clave = 'dashboard:' . $inicio->toDateString() . ':' . $fin->toDateString();
        if ($request->boolean('refrescar')) {
            Cache::forget($clave);
        }

        $metricas = Cache::remember($clave, self::CACHE_SEGUNDOS, function () use ($inicio, $fin) {
            return [
                ...(new DashboardMetrics($inicio, $fin))->todo(),
                'generado' => now(config('app.local_timezone'))->format('H:i'),
            ];
        });

        return Inertia::render('Dashboard', [
            'metricas' => $metricas,
        ]);
    }
}
