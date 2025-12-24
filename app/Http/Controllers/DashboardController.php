<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Fecha actual y hace 6 meses
        $fechaActual = Carbon::now();
        $hace6Meses = Carbon::now()->subMonths(6);

        // KPIs del mes actual
        $estadisticas = [
            'total_reportes' => DB::table('reportes')
                ->whereMonth('created_at', $fechaActual->month)
                ->whereYear('created_at', $fechaActual->year)
                ->count(),
            
            'ingresos_mes' => DB::table('reporte_estudio')
                ->join('reportes', 'reporte_estudio.reporte_id', '=', 'reportes.id')
                ->whereMonth('reportes.created_at', $fechaActual->month)
                ->whereYear('reportes.created_at', $fechaActual->year)
                ->sum('reporte_estudio.precio'),
            
            'total_estudios' => DB::table('reporte_estudio')
                ->join('reportes', 'reporte_estudio.reporte_id', '=', 'reportes.id')
                ->whereMonth('reportes.created_at', $fechaActual->month)
                ->whereYear('reportes.created_at', $fechaActual->year)
                ->count(),
        ];

        // Calcular ticket promedio
        $estadisticas['ticket_promedio'] = $estadisticas['total_reportes'] > 0 
            ? $estadisticas['ingresos_mes'] / $estadisticas['total_reportes'] 
            : 0;

        // Ingresos por mes (últimos 6 meses)
        $ingresosPorMes = DB::table('reportes')
            ->join('reporte_estudio', 'reportes.id', '=', 'reporte_estudio.reporte_id')
            ->select(
                DB::raw("TO_CHAR(reportes.created_at, 'Mon YYYY') as mes"),
                DB::raw('SUM(reporte_estudio.precio) as total')
            )
            ->where('reportes.created_at', '>=', $hace6Meses)
            ->groupBy(DB::raw("TO_CHAR(reportes.created_at, 'Mon YYYY')"), DB::raw("DATE_TRUNC('month', reportes.created_at)"))
            ->orderBy(DB::raw("DATE_TRUNC('month', reportes.created_at)"))
            ->get();

        // Reportes por mes (últimos 6 meses)
        $reportesPorMes = DB::table('reportes')
            ->select(
                DB::raw("TO_CHAR(created_at, 'Mon YYYY') as mes"),
                DB::raw('COUNT(*) as total')
            )
            ->where('created_at', '>=', $hace6Meses)
            ->groupBy(DB::raw("TO_CHAR(created_at, 'Mon YYYY')"), DB::raw("DATE_TRUNC('month', created_at)"))
            ->orderBy(DB::raw("DATE_TRUNC('month', created_at)"))
            ->get();

        // Top 10 estudios más vendidos
        $estudiosPopulares = DB::table('reporte_estudio')
            ->join('estudios', 'reporte_estudio.estudio_id', '=', 'estudios.id')
            ->select(
                'estudios.nombre',
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('estudios.id', 'estudios.nombre')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->get();

        // Distribución por género
        $reportesPorGenero = [
            'masculino' => DB::table('reportes')->where('sexo', 'Masculino')->count(),
            'femenino' => DB::table('reportes')->where('sexo', 'Femenino')->count(),
            'no_definir' => DB::table('reportes')->where('sexo', 'No definir')->count(),
        ];

        // Reportes por día de la semana
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
        ]);
    }
}