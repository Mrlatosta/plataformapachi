<?php

namespace App\Http\Controllers;

use App\Models\Comision;
use App\Models\Medico;
use App\Services\Comisiones;
use App\Services\ReportePdf;
use App\Support\Xlsx;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * T12: reporte de comisiones por medico que refiere. Solo Administrador
 * (las rutas estan en el grupo rol:admin).
 */
class ComisionController extends Controller
{
    public function page()
    {
        return Inertia::render('Comisiones/Index', [
            'medicos' => Medico::orderBy('nombre')->get(['id', 'nombre', 'porcentaje_comision', 'activo']),
            'base' => config('comisiones.base'),
        ]);
    }

    private function filtros(Request $request): array
    {
        $validados = $request->validate([
            'desde' => 'nullable|date',
            'hasta' => 'nullable|date',
            'medico_id' => 'nullable|integer|exists:medicos,id',
            'estado' => 'nullable|in:pendiente,pagada',
        ]);

        return [
            'desde' => ! empty($validados['desde']) ? Carbon::parse($validados['desde'])->startOfDay() : now()->startOfMonth(),
            'hasta' => ! empty($validados['hasta']) ? Carbon::parse($validados['hasta'])->endOfDay() : now()->endOfDay(),
            'medico_id' => $validados['medico_id'] ?? null,
            'estado' => $validados['estado'] ?? null,
        ];
    }

    /** Comisiones del periodo (fecha de la orden = toma de muestra). */
    private function consulta(array $f)
    {
        return Comision::query()
            ->whereHas('reporte', fn ($q) => $q->whereBetween('toma_muestra', [$f['desde'], $f['hasta']]))
            ->when($f['medico_id'], fn ($q, $id) => $q->where('medico_id', $id))
            ->when($f['estado'], fn ($q, $estado) => $q->where('estado', $estado));
    }

    private function filas(array $f): Collection
    {
        return $this->consulta($f)
            ->with(['medico:id,nombre', 'reporte:id,folio,toma_muestra,nombre_cliente', 'reporte.estudios.estudio:id,nombre', 'pagadaPor:id,name'])
            ->get()
            ->sortBy(fn (Comision $c) => [$c->medico->nombre ?? '', $c->reporte->toma_muestra])
            ->values()
            ->map(fn (Comision $c) => [
                'id' => $c->id,
                'reporte_id' => $c->reporte_id,
                'medico_id' => $c->medico_id,
                'medico' => $c->medico->nombre ?? '—',
                'folio' => $c->reporte->folio,
                'fecha' => Carbon::parse($c->reporte->toma_muestra)->format('Y-m-d'),
                'paciente' => $c->reporte->nombre_cliente,
                'estudios' => $c->reporte->estudios->map(fn ($e) => $e->estudio->nombre ?? '')->filter()->implode(', '),
                'monto' => (float) $c->monto_base,
                'porcentaje' => (float) $c->porcentaje,
                'comision' => (float) $c->comision,
                'estado' => $c->estado,
                'pagada_at' => $c->pagada_at?->format('Y-m-d'),
                'pagada_por' => $c->pagadaPor?->name,
            ]);
    }

    /** Totales por medico y general. */
    private function resumen(Collection $filas): array
    {
        $porMedico = $filas->groupBy('medico_id')->map(fn (Collection $g) => [
            'medico_id' => $g->first()['medico_id'],
            'medico' => $g->first()['medico'],
            'ordenes' => $g->count(),
            'monto' => round($g->sum('monto'), 2),
            'comision' => round($g->sum('comision'), 2),
            'pendiente' => round($g->where('estado', Comision::PENDIENTE)->sum('comision'), 2),
            'pagada' => round($g->where('estado', Comision::PAGADA)->sum('comision'), 2),
        ])->values();

        return [
            'por_medico' => $porMedico,
            'total' => [
                'ordenes' => $filas->count(),
                'monto' => round($filas->sum('monto'), 2),
                'comision' => round($filas->sum('comision'), 2),
                'pendiente' => round($filas->where('estado', Comision::PENDIENTE)->sum('comision'), 2),
                'pagada' => round($filas->where('estado', Comision::PAGADA)->sum('comision'), 2),
            ],
        ];
    }

    public function index(Request $request)
    {
        $f = $this->filtros($request);
        $filas = $this->filas($f);

        return response()->json([
            'desde' => $f['desde']->toDateString(),
            'hasta' => $f['hasta']->toDateString(),
            'filas' => $filas,
            ...$this->resumen($filas),
        ]);
    }

    /**
     * Marca como pagadas las comisiones pendientes del periodo (y medico) filtrado.
     */
    public function pagar(Request $request)
    {
        $f = $this->filtros($request);

        $marcadas = DB::transaction(fn () => $this->consulta([...$f, 'estado' => null])
            ->where('estado', Comision::PENDIENTE)
            ->update([
                'estado' => Comision::PAGADA,
                'pagada_at' => now(),
                'pagada_por' => $request->user()->id,
            ]));

        return response()->json(['marcadas' => $marcadas]);
    }

    /** Deshace el pago de una comision (p. ej. se marco por error). */
    public function revertir($id)
    {
        $comision = Comision::with('reporte.estudios')->findOrFail($id);
        $comision->update(['estado' => Comision::PENDIENTE, 'pagada_at' => null, 'pagada_por' => null]);

        // Si la orden cambio mientras estaba pagada, se recalcula
        Comisiones::sincronizar($comision->reporte);

        return response()->json(['message' => 'La comisión volvió a pendiente']);
    }

    private function titulo(array $f): string
    {
        $medico = $f['medico_id'] ? Medico::whereKey($f['medico_id'])->value('nombre') : null;

        return 'Comisiones '.($medico ? "de {$medico} " : '')
            .'del '.$f['desde']->format('d/m/Y').' al '.$f['hasta']->format('d/m/Y')
            .($f['estado'] ? ' ('.($f['estado'] === Comision::PAGADA ? 'pagadas' : 'pendientes').')' : '');
    }

    private function nombreArchivo(array $f, string $extension): string
    {
        $medico = $f['medico_id'] ? Medico::whereKey($f['medico_id'])->value('nombre') : 'todos';

        return 'Comisiones '.$medico.' '.$f['desde']->format('Y-m-d').' a '.$f['hasta']->format('Y-m-d').'.'.$extension;
    }

    public function exportarExcel(Request $request)
    {
        $f = $this->filtros($request);
        $filas = $this->filas($f);
        $resumen = $this->resumen($filas);

        $hoja = [
            [['v' => 'Laboratorio de Análisis Clínicos BIOLAB', 's' => 'titulo']],
            [['v' => $this->titulo($f), 's' => 'negrita']],
            [],
            array_map(fn ($t) => ['v' => $t, 's' => 'encabezado'], ['Médico', 'Folio', 'Fecha', 'Paciente', 'Estudios', 'Monto cobrado', '%', 'Comisión', 'Estado', 'Fecha de pago']),
        ];

        foreach ($resumen['por_medico'] as $medico) {
            foreach ($filas->where('medico_id', $medico['medico_id']) as $fila) {
                $hoja[] = [
                    $fila['medico'], $fila['folio'], $fila['fecha'], $fila['paciente'], $fila['estudios'],
                    ['v' => $fila['monto'], 's' => 'dinero'],
                    ['v' => $fila['porcentaje'], 's' => 'numero'],
                    ['v' => $fila['comision'], 's' => 'dinero'],
                    $fila['estado'] === Comision::PAGADA ? 'Pagada' : 'Pendiente',
                    $fila['pagada_at'],
                ];
            }
            $hoja[] = [
                ['v' => "Total {$medico['medico']} ({$medico['ordenes']} órdenes)", 's' => 'negrita'], null, null, null, null,
                ['v' => $medico['monto'], 's' => 'dinero_negrita'], null,
                ['v' => $medico['comision'], 's' => 'dinero_negrita'],
                ['v' => 'Pendiente: $'.number_format($medico['pendiente'], 2), 's' => 'negrita'],
            ];
            $hoja[] = [];
        }

        $hoja[] = [
            ['v' => "TOTAL GENERAL ({$resumen['total']['ordenes']} órdenes)", 's' => 'negrita'], null, null, null, null,
            ['v' => $resumen['total']['monto'], 's' => 'dinero_negrita'], null,
            ['v' => $resumen['total']['comision'], 's' => 'dinero_negrita'],
            ['v' => 'Pendiente: $'.number_format($resumen['total']['pendiente'], 2), 's' => 'negrita'],
        ];

        $contenido = Xlsx::generar('Comisiones', $hoja, [28, 11, 11, 28, 40, 14, 7, 13, 12, 13]);

        return response($contenido, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$this->nombreArchivo($f, 'xlsx').'"',
        ]);
    }

    public function exportarPdf(Request $request)
    {
        $f = $this->filtros($request);
        $filas = $this->filas($f);

        $pdf = Pdf::loadView('pdf.comisiones', [
            'titulo' => $this->titulo($f),
            'filas' => $filas,
            ...$this->resumen($filas),
            'base' => config('comisiones.base'),
        ])->setPaper('letter', 'landscape');

        ReportePdf::numerarPaginas($pdf, 8);

        return $pdf->download($this->nombreArchivo($f, 'pdf'));
    }
}
