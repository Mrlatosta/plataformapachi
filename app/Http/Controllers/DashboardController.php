<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Obtener fechas desde la request o usar valores por defecto
        $fechaInicio = $request->input('fecha_inicio') 
            ? Carbon::parse($request->input('fecha_inicio'))->startOfDay()
            : Carbon::now()->subMonths(6)->startOfDay();
        
        $fechaFin = $request->input('fecha_fin')
            ? Carbon::parse($request->input('fecha_fin'))->endOfDay()
            : Carbon::now()->endOfDay();
        
        // Fecha actual para comparaciones
        $fechaActual = Carbon::now();

        // KPIs según el rango de fechas seleccionado
        $estadisticas = [
            'total_reportes' => DB::table('reportes')
                ->whereBetween('created_at', [$fechaInicio, $fechaFin])
                ->count(),
            
            'ingresos_mes' => DB::table('reporte_estudio')
                ->join('reportes', 'reporte_estudio.reporte_id', '=', 'reportes.id')
                ->whereBetween('reportes.created_at', [$fechaInicio, $fechaFin])
                ->sum('reporte_estudio.precio'),
            
            'total_estudios' => DB::table('reporte_estudio')
                ->join('reportes', 'reporte_estudio.reporte_id', '=', 'reportes.id')
                ->whereBetween('reportes.created_at', [$fechaInicio, $fechaFin])
                ->count(),
        ];

        // Calcular ticket promedio
        $estadisticas['ticket_promedio'] = $estadisticas['total_reportes'] > 0 
            ? $estadisticas['ingresos_mes'] / $estadisticas['total_reportes'] 
            : 0;

        // Ingresos por mes según rango seleccionado
        $ingresosPorMes = DB::table('reportes')
            ->join('reporte_estudio', 'reportes.id', '=', 'reporte_estudio.reporte_id')
            ->select(
                DB::raw("TO_CHAR(reportes.created_at, 'Mon YYYY') as mes"),
                DB::raw('SUM(reporte_estudio.precio) as total')
            )
            ->whereBetween('reportes.created_at', [$fechaInicio, $fechaFin])
            ->groupBy(DB::raw("TO_CHAR(reportes.created_at, 'Mon YYYY')"), DB::raw("DATE_TRUNC('month', reportes.created_at)"))
            ->orderBy(DB::raw("DATE_TRUNC('month', reportes.created_at)"))
            ->get();

        // Reportes por mes según rango seleccionado
        $reportesPorMes = DB::table('reportes')
            ->select(
                DB::raw("TO_CHAR(created_at, 'Mon YYYY') as mes"),
                DB::raw('COUNT(*) as total')
            )
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->groupBy(DB::raw("TO_CHAR(created_at, 'Mon YYYY')"), DB::raw("DATE_TRUNC('month', created_at)"))
            ->orderBy(DB::raw("DATE_TRUNC('month', created_at)"))
            ->get();

        // Top 10 estudios más vendidos según rango seleccionado
        $estudiosPopulares = DB::table('reporte_estudio')
            ->join('estudios', 'reporte_estudio.estudio_id', '=', 'estudios.id')
            ->join('reportes', 'reporte_estudio.reporte_id', '=', 'reportes.id')
            ->select(
                'estudios.nombre',
                DB::raw('COUNT(*) as total')
            )
            ->whereBetween('reportes.created_at', [$fechaInicio, $fechaFin])
            ->groupBy('estudios.id', 'estudios.nombre')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->get();

        // Distribución por género según rango seleccionado
        $reportesPorGenero = [
            'masculino' => DB::table('reportes')
                ->where('sexo', 'Masculino')
                ->whereBetween('created_at', [$fechaInicio, $fechaFin])
                ->count(),
            'femenino' => DB::table('reportes')
                ->where('sexo', 'Femenino')
                ->whereBetween('created_at', [$fechaInicio, $fechaFin])
                ->count(),
            'no_definir' => DB::table('reportes')
                ->where('sexo', 'No definir')
                ->whereBetween('created_at', [$fechaInicio, $fechaFin])
                ->count(),
        ];

        // Reportes por día de la semana según rango seleccionado
        $reportesPorDia = DB::table('reportes')
            ->select(
                DB::raw("CASE 
                    WHEN EXTRACT(DOW FROM created_at) = 0 THEN 'Domingo'
                    WHEN EXTRACT(DOW FROM created_at) = 1 THEN 'Lunes'
                    WHEN EXTRACT(DOW FROM created_at) = 2 THEN 'Martes'
                    WHEN EXTRACT(DOW FROM created_at) = 3 THEN 'Miércoles'
                    WHEN EXTRACT(DOW FROM created_at) = 4 THEN 'Jueves'
                    WHEN EXTRACT(DOW FROM created_at) = 5 THEN 'Viernes'
                    WHEN EXTRACT(DOW FROM created_at) = 6 THEN 'Sábado'
                END as dia"),
                DB::raw('COUNT(*) as total'),
                DB::raw('EXTRACT(DOW FROM created_at) as orden')
            )
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->groupBy(DB::raw('EXTRACT(DOW FROM created_at)'))
            ->orderBy('orden')
            ->get();

        return Inertia::render('Dashboard', [
            'estadisticas' => $estadisticas,
            'ingresosPorMes' => $ingresosPorMes,
            'reportesPorMes' => $reportesPorMes,
            'estudiosPopulares' => $estudiosPopulares,
            'reportesPorGenero' => $reportesPorGenero,
            'reportesPorDia' => $reportesPorDia,
            'fechaInicio' => $fechaInicio->format('Y-m-d'),
            'fechaFin' => $fechaFin->format('Y-m-d'),
        ]);
    }
}