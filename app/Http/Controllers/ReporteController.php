<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Estudio;
use App\Models\Examen;
use App\Models\Reporte;
use App\Models\ReporteEstudio;
use App\Models\Resultado;
use App\Models\ResultadoExamen;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use App\Support\RangoReferencia;
use Barryvdh\DomPDF\Facade\Pdf;
use Inertia\Inertia;



class ReporteController extends Controller
{

/** Cache de valores de referencia para no consultar el mismo examen varias veces */
private array $valoresReferencia = [];

/**
 * Direccion ('alto' / 'bajo' / null) de un resultado marcado como fuera de rango.
 * Respeta la seleccion manual del laboratorio y, si no viene, la deduce
 * comparando el resultado contra el valor de referencia del examen.
 */
private function resolverDireccionRango($examenId, $resultado, bool $fueraRango, $direccionManual = null): ?string
{
    if (! $fueraRango) {
        return null;
    }

    $direccion = strtolower((string) $direccionManual);
    if (in_array($direccion, [RangoReferencia::ALTO, RangoReferencia::BAJO], true)) {
        return $direccion;
    }

    if (! array_key_exists($examenId, $this->valoresReferencia)) {
        $this->valoresReferencia[$examenId] = Examen::whereKey($examenId)->value('valor_referencia');
    }

    return RangoReferencia::direccion($resultado, $this->valoresReferencia[$examenId]);
}

private function applyPageNumbers($pdf, float $x = 500, float $y = 820, int $size = 9): void
{
    $dompdf = $pdf->getDomPDF();
    $dompdf->render();
    $canvas = $dompdf->getCanvas();
    $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');
    $canvas->page_text($x, $y, 'Pagina {PAGE_NUM} de {PAGE_COUNT}', $font, $size, [0.4, 0.4, 0.4]);
}

/**
 * Reglas de validacion compartidas por el guardado y la previsualizacion,
 * para que la vista previa refleje exactamente lo que se va a guardar.
 */
private function reglasReporte(): array
{
    return [
        'cliente.nombre' => 'required|string|max:255',
        'cliente.fecha_nacimiento' => 'required|date',
        'cliente.sexo' => 'required|in:Masculino,Femenino',
        'cliente.email' => 'nullable|email|max:255',
        'cliente.edad' => 'nullable|integer|min:0',
        'toma_muestra' => 'required|date',
        'fecha_reporte' => 'required|date',
        'fecha_validacion' => 'required|date',
        'medico_solicitante' => 'nullable|string|max:255',
        'medico_id' => 'nullable|exists:medicos,id',
        'aplica_iva' => 'nullable|boolean',
        'estudios' => 'required|array|min:1',
        'estudios.*.id' => 'required|exists:estudios,id',
        'estudios.*.orden' => 'nullable|integer',
        'estudios.*.tipo_muestra' => 'nullable|string|max:255',
        'estudios.*.metodo' => 'nullable|string|max:255',
        'estudios.*.elaboro' => 'nullable|string|max:255',
        'estudios.*.valido' => 'nullable|string|max:255',
        'estudios.*.precio' => 'required|numeric|min:0',
        'estudios.*.observaciones' => 'nullable|string',
        'estudios.*.examenes' => 'required|array|min:1',
        'estudios.*.examenes.*.id' => 'required|exists:examenes,id',
        'estudios.*.examenes.*.resultado' => 'nullable|string',
        'estudios.*.examenes.*.fuera_rango' => 'nullable|boolean',
        'estudios.*.examenes.*.direccion_rango' => 'nullable|in:alto,bajo',
    ];
}

private function mensajesReporte(): array
{
    return [
        'cliente.nombre.required' => 'El nombre del paciente es obligatorio',
        'cliente.fecha_nacimiento.required' => 'La fecha de nacimiento es obligatoria',
        'cliente.sexo.required' => 'El sexo del paciente es obligatorio',
        'toma_muestra.required' => 'La fecha de toma de muestra es obligatoria',
        'fecha_reporte.required' => 'La fecha de reporte es obligatoria',
        'fecha_validacion.required' => 'La fecha de validación es obligatoria',
        'estudios.required' => 'Debe agregar al menos un estudio',
        'estudios.min' => 'Debe agregar al menos un estudio',
        'estudios.*.precio.required' => 'Cada estudio debe tener un precio',
        'estudios.*.precio.min' => 'El precio debe ser mayor o igual a 0',
    ];
}

/**
 * Folio que le tocaria al siguiente reporte. En la vista previa es solo
 * informativo: no se reserva nada hasta que el reporte se guarda.
 */
private function folioProyectado(): string
{
    $ultimoFolio = Reporte::orderBy('id', 'desc')->value('folio');
    $numero = $ultimoFolio ? (int) preg_replace('/\D/', '', $ultimoFolio) + 1 : 1;

    return 'RPT-' . str_pad($numero, 4, '0', STR_PAD_LEFT);
}

public function store(Request $request)
{
    // Validar datos antes de guardar
    $validated = $request->validate($this->reglasReporte(), $this->mensajesReporte());

    $folio = $this->folioProyectado();

    $cliente = $validated['cliente'];

    // Crear reporte
    $reporte = Reporte::create([
        'folio' => $folio,
        'nombre_cliente' => $cliente['nombre'],
        'email' => $cliente['email'] ?? null,
        'fecha_nacimiento' => $cliente['fecha_nacimiento'],
        'edad' => $cliente['edad'] ?? null,
        'sexo' => $cliente['sexo'],
        'toma_muestra' => $validated['toma_muestra'],
        'fecha_reporte' => $validated['fecha_reporte'],
        'fecha_validacion' => $validated['fecha_validacion'],
        'medico_solicitante' => $validated['medico_solicitante'] ?? null,
        'medico_id' => $validated['medico_id'] ?? null,
        'aplica_iva' => filter_var($validated['aplica_iva'] ?? false, FILTER_VALIDATE_BOOLEAN),
    ]);

    // Crear estudios y resultados
    foreach ($validated['estudios'] as $indice => $estudioData) {
        $reporteEstudio = $reporte->estudios()->create([
            'estudio_id' => $estudioData['id'],
            'orden' => $estudioData['orden'] ?? $indice,
            'tipo_muestra' => $estudioData['tipo_muestra'] ?? null,
            'metodo' => $estudioData['metodo'] ?? null,
            'elaboro' => $estudioData['elaboro'] ?? null,
            'valido' => $estudioData['valido'] ?? null,
            'precio' => $estudioData['precio'],
            'observaciones' => $estudioData['observaciones'] ?? null,
        ]);

        foreach ($estudioData['examenes'] as $resultado) {
            $fueraRango = filter_var($resultado['fuera_rango'] ?? false, FILTER_VALIDATE_BOOLEAN);

            // Debug: Log del valor de fuera_rango recibido
            \Log::info('Guardando resultado', [
                'examen_id' => $resultado['id'],
                'fuera_rango_raw' => $resultado['fuera_rango'] ?? 'NO DEFINIDO',
                'fuera_rango_tipo' => gettype($resultado['fuera_rango'] ?? null),
                'fuera_rango_filtered' => $fueraRango,
            ]);

            $reporteEstudio->resultados()->create([
                'examen_id' => $resultado['id'],
                'resultado' => $resultado['resultado'] ?? null,
                'fuera_rango' => $fueraRango,
                'direccion_rango' => $this->resolverDireccionRango(
                    $resultado['id'],
                    $resultado['resultado'] ?? null,
                    $fueraRango,
                    $resultado['direccion_rango'] ?? null,
                ),
            ]);
        }
    }

    // 🔹 Cargar reporte completo para el PDF (con relaciones)
    $reporte->load([
        'estudios.estudio',
        'estudios.resultados.examen'
    ]);

    // 🔹 Generar PDF de la orden de trabajo
    $pdfOrden = Pdf::loadView('pdf.orden_trabajo', compact('reporte'));
    $this->applyPageNumbers($pdfOrden, 480, 815, 8);

    // Opcional: Guardar el archivo en el servidor
    // Storage::put("ordenes/orden-{$reporte->folio}.pdf", $pdfOrden->output());

    // 🔹 Devolver respuesta con enlace al PDF (abrir en nueva pestaña)
  return response()->json([
    'message' => 'Reporte guardado correctamente',
    'id' => $reporte->id,
    'folio' => $reporte->folio
]);


}



    /**
     * Arma un Reporte completo EN MEMORIA (sin tocar la base de datos) a partir
     * del mismo payload que envia el formulario de captura. Se usa para la
     * vista previa: las relaciones se inyectan a mano para que las plantillas
     * PDF funcionen igual que con un reporte ya guardado.
     */
    private function construirReporteBorrador(array $validated): Reporte
    {
        $cliente = $validated['cliente'];

        $reporte = new Reporte([
            'folio' => $this->folioProyectado(),
            'nombre_cliente' => $cliente['nombre'],
            'email' => $cliente['email'] ?? null,
            'fecha_nacimiento' => $cliente['fecha_nacimiento'],
            'edad' => $cliente['edad'] ?? null,
            'sexo' => $cliente['sexo'],
            'toma_muestra' => $validated['toma_muestra'],
            'fecha_reporte' => $validated['fecha_reporte'],
            'fecha_validacion' => $validated['fecha_validacion'],
            'medico_solicitante' => $validated['medico_solicitante'] ?? null,
            'medico_id' => $validated['medico_id'] ?? null,
            'aplica_iva' => filter_var($validated['aplica_iva'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ]);

        // Catalogos precargados para no consultar dentro del bucle
        $estudios = Estudio::whereIn('id', collect($validated['estudios'])->pluck('id'))
            ->get()
            ->keyBy('id');

        $examenIds = collect($validated['estudios'])
            ->flatMap(fn ($estudio) => collect($estudio['examenes'])->pluck('id'))
            ->unique();
        $examenes = Examen::whereIn('id', $examenIds)->get()->keyBy('id');

        $reporteEstudios = collect($validated['estudios'])
            ->values()
            ->map(function ($estudioData, $indice) use ($estudios, $examenes) {
                $reporteEstudio = new ReporteEstudio([
                    'estudio_id' => $estudioData['id'],
                    'orden' => $estudioData['orden'] ?? $indice,
                    'tipo_muestra' => $estudioData['tipo_muestra'] ?? null,
                    'metodo' => $estudioData['metodo'] ?? null,
                    'elaboro' => $estudioData['elaboro'] ?? null,
                    'valido' => $estudioData['valido'] ?? null,
                    'precio' => $estudioData['precio'],
                    'observaciones' => $estudioData['observaciones'] ?? null,
                ]);

                $reporteEstudio->setRelation('estudio', $estudios->get($estudioData['id']));

                $resultados = collect($estudioData['examenes'])->map(function ($examenData) use ($examenes) {
                    $fueraRango = filter_var($examenData['fuera_rango'] ?? false, FILTER_VALIDATE_BOOLEAN);

                    $resultado = new ResultadoExamen([
                        'examen_id' => $examenData['id'],
                        'resultado' => $examenData['resultado'] ?? null,
                        'fuera_rango' => $fueraRango,
                        'direccion_rango' => $this->resolverDireccionRango(
                            $examenData['id'],
                            $examenData['resultado'] ?? null,
                            $fueraRango,
                            $examenData['direccion_rango'] ?? null,
                        ),
                    ]);

                    $resultado->setRelation('examen', $examenes->get($examenData['id']));

                    return $resultado;
                });

                $reporteEstudio->setRelation('resultados', new EloquentCollection($resultados->all()));

                return $reporteEstudio;
            })
            ->sortBy('orden')
            ->values();

        $reporte->setRelation('estudios', new EloquentCollection($reporteEstudios->all()));

        return $reporte;
    }

    /**
     * Vista previa del reporte (o de la orden de trabajo) ANTES de guardarlo.
     * No persiste nada: el usuario puede volver a editar o confirmar el guardado.
     */
    public function previsualizarBorrador(Request $request)
    {
        $validated = $request->validate($this->reglasReporte(), $this->mensajesReporte());

        $reporte = $this->construirReporteBorrador($validated);

        if ($request->input('documento') === 'orden') {
            $subtotal = $reporte->subtotal;
            $iva = $reporte->monto_iva;
            $total = $reporte->total;

            $pdf = Pdf::loadView('pdf.orden_trabajo', compact('reporte', 'subtotal', 'iva', 'total'))
                ->setPaper('A4', 'portrait');

            $this->applyPageNumbers($pdf, 480, 815, 8);

            return $pdf->stream("vista-previa-orden-{$reporte->folio}.pdf");
        }

        $pdf = Pdf::loadView('pdf.reporte_biolab', compact('reporte'));
        $this->applyPageNumbers($pdf, 490, 815, 9);

        return $pdf->stream("vista-previa-reporte-{$reporte->folio}.pdf");
    }

    public function generarPDF($reporteId)
    {
        $reporte = Reporte::with([
    'estudios.estudio',
    'estudios.resultados.examen'
])->findOrFail($reporteId);

        $pdf = Pdf::loadView('pdf.reporte_biolab', compact('reporte'));
        $this->applyPageNumbers($pdf, 490, 815, 9);

        return $pdf->download("reporte-{$reporte->folio}.pdf");
    }

    public function previsualizarPDF($reporteId)
    {
        $reporte = Reporte::with([
            'estudios.estudio',
            'estudios.resultados.examen'
        ])->findOrFail($reporteId);

        $pdf = Pdf::loadView('pdf.reporte_biolab', compact('reporte'));
        $this->applyPageNumbers($pdf, 490, 815, 9);

        return $pdf->stream("reporte-{$reporte->folio}.pdf");
    }

public function generarOrdenTrabajo($reporteId)
{
    $reporte = Reporte::with(['estudios.estudio'])->findOrFail($reporteId);

    $subtotal = $reporte->subtotal;   // 💰 suma de los precios de los estudios
    $iva = $reporte->monto_iva;       // 💰 0 si la orden no aplica IVA
    $total = $reporte->total;         // 💰 subtotal + IVA

    $pdf = Pdf::loadView('pdf.orden_trabajo', compact('reporte', 'subtotal', 'iva', 'total'))
        ->setPaper('A4', 'portrait');

    $this->applyPageNumbers($pdf, 480, 815, 8);

    return $pdf->download("orden-trabajo-{$reporte->folio}.pdf");
}


    public function buscarPorFolio($folio)
{
    $reporte = Reporte::with(['estudios.estudio', 'estudios.resultados.examen'])
        ->where('folio', $folio)
        ->firstOrFail();

    return response()->json($reporte);
}

public function actualizarReporte(Request $request, $id)
{
    $reporte = Reporte::findOrFail($id);
    $reporte->update($request->only([
        'toma_muestra', 'fecha_reporte', 'fecha_validacion', 'medico_solicitante', 'medico_id'
    ]));

    if ($request->has('aplica_iva')) {
        $reporte->update([
            'aplica_iva' => filter_var($request->input('aplica_iva'), FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    $cliente = $request->input('cliente', []);

    // La fecha puede llegar como '2003-09-06' o '2003-09-06T00:00:00.000000Z'
    $fechaNacimiento = !empty($cliente['fecha_nacimiento'])
        ? substr((string) $cliente['fecha_nacimiento'], 0, 10)
        : $reporte->fecha_nacimiento;

    // Si cambio la fecha de nacimiento y no llego una edad, se recalcula
    // a la fecha de toma de muestra del reporte.
    $edad = $cliente['edad'] ?? null;
    if (($edad === null || $edad === '') && $fechaNacimiento) {
        $referencia = $reporte->toma_muestra ? \Carbon\Carbon::parse($reporte->toma_muestra) : now();
        $edad = \Carbon\Carbon::parse($fechaNacimiento)->diffInYears($referencia);
    }

    $reporte->update([
        'nombre_cliente' => $cliente['nombre'] ?? $reporte->nombre_cliente,
        'email' => $cliente['email'] ?? null,
        'fecha_nacimiento' => $fechaNacimiento,
        'edad' => $edad !== null && $edad !== '' ? (int) $edad : null,
        'sexo' => $cliente['sexo'] ?? null,
    ]);

    $idsNuevos = collect($request->input('estudios'))->pluck('id')->filter();
    $reporte->estudios()
        ->whereNotIn('id', $idsNuevos)
        ->each(function ($estudio) {
            $estudio->resultados()->delete();
            $estudio->delete();
        });

   // Actualizar o crear estudios
    foreach ($request->input('estudios', []) as $indice => $estudioData) {
    $reporteEstudio = null;

    if (!empty($estudioData['id'])) {
        // Si ya existe, lo buscamos
        $reporteEstudio = $reporte->estudios()->find($estudioData['id']);
    }

    if ($reporteEstudio) {
        // Actualizar
        $reporteEstudio->update([
            'orden' => $estudioData['orden'] ?? $indice,
            'tipo_muestra' => $estudioData['tipo_muestra'] ?? null,
            'metodo' => $estudioData['metodo'] ?? null,
            'elaboro' => $estudioData['elaboro'] ?? null,
            'valido' => $estudioData['valido'] ?? null,
            'precio' => $estudioData['precio'] ?? 0,
            'observaciones' => $estudioData['observaciones'] ?? null,
        ]);

        foreach ($estudioData['resultados'] ?? [] as $resultadoData) {
            $fueraRango = filter_var($resultadoData['fuera_rango'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if (!empty($resultadoData['id'])) {
                $resultado = $reporteEstudio->resultados()->find($resultadoData['id']);
                if ($resultado) {
                    $resultado->update([
                        'resultado' => $resultadoData['resultado'] ?? null,
                        'fuera_rango' => $fueraRango,
                        'direccion_rango' => $this->resolverDireccionRango(
                            $resultado->examen_id,
                            $resultadoData['resultado'] ?? null,
                            $fueraRango,
                            $resultadoData['direccion_rango'] ?? null,
                        ),
                    ]);
                }
            } else {
                // Si no tiene id, es nuevo
                $examenId = $resultadoData['examen_id'] ?? $resultadoData['id'];

                $reporteEstudio->resultados()->create([
                    'examen_id' => $examenId,
                    'resultado' => $resultadoData['resultado'] ?? null,
                    'fuera_rango' => $fueraRango,
                    'direccion_rango' => $this->resolverDireccionRango(
                        $examenId,
                        $resultadoData['resultado'] ?? null,
                        $fueraRango,
                        $resultadoData['direccion_rango'] ?? null,
                    ),
                ]);
            }
        }
    } else {
        // Crear nuevo estudio
        $nuevo = $reporte->estudios()->create([
            'estudio_id' => $estudioData['estudio_id'] ?? $estudioData['id'],
            'orden' => $estudioData['orden'] ?? $indice,
            'tipo_muestra' => $estudioData['tipo_muestra'] ?? null,
            'metodo' => $estudioData['metodo'] ?? null,
            'elaboro' => $estudioData['elaboro'] ?? null,
            'valido' => $estudioData['valido'] ?? null,
            'precio' => $estudioData['precio'] ?? 0,
            'observaciones' => $estudioData['observaciones'] ?? null,
        ]);

        foreach ($estudioData['resultados'] ?? [] as $r) {
            $examenId = $r['examen_id'] ?? $r['id'];
            $fueraRango = filter_var($r['fuera_rango'] ?? false, FILTER_VALIDATE_BOOLEAN);

            $nuevo->resultados()->create([
                'examen_id' => $examenId,
                'resultado' => $r['resultado'] ?? null,
                'fuera_rango' => $fueraRango,
                'direccion_rango' => $this->resolverDireccionRango(
                    $examenId,
                    $r['resultado'] ?? null,
                    $fueraRango,
                    $r['direccion_rango'] ?? null,
                ),
            ]);
        }
    }
}

    return response()->json(['message' => 'Reporte actualizado correctamente']);
}

public function index(Request $request)
    {
        $query = Reporte::with(['estudios.estudio'])
            ->orderBy('created_at', 'desc');

        // Filtros
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('folio', 'like', "%{$search}%")
                  ->orWhere('nombre_cliente', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('fecha_desde') && $request->fecha_desde) {
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        }

        if ($request->has('fecha_hasta') && $request->fecha_hasta) {
            $query->whereDate('created_at', '<=', $request->fecha_hasta);
        }

        $reportes = $query->paginate(15)->through(function ($reporte) {
            return [
                'id' => $reporte->id,
                'folio' => $reporte->folio,
                'nombre_cliente' => $reporte->nombre_cliente,
                'email' => $reporte->email,
                'fecha_nacimiento' => $reporte->fecha_nacimiento,
                'edad' => $reporte->edad,
                'sexo' => $reporte->sexo,
                'toma_muestra' => $reporte->toma_muestra,
                'fecha_reporte' => $reporte->fecha_reporte,
                'fecha_validacion' => $reporte->fecha_validacion,
                'medico_solicitante' => $reporte->medico_solicitante,
                'created_at' => $reporte->created_at,
                'aplica_iva' => (bool) $reporte->aplica_iva,
                'total_estudios' => $reporte->estudios->count(),
                'subtotal' => $reporte->subtotal,
                'iva' => $reporte->monto_iva,
                'total_precio' => $reporte->total,
            ];
        });

        return Inertia::render('Reportes/Index', [
            'reportes' => $reportes,
            'filters' => $request->only(['search', 'fecha_desde', 'fecha_hasta'])
        ]);
    }

    /**
     * Mostrar detalles de un reporte específico
     */
    public function show($id)
    {
        $reporte = Reporte::with([
            'estudios.estudio',
            'estudios.resultados.examen'
        ])->findOrFail($id);

        return Inertia::render('Reportes/Show', [
            'reporte' => [
                'id' => $reporte->id,
                'folio' => $reporte->folio,
                'nombre_cliente' => $reporte->nombre_cliente,
                'email' => $reporte->email,
                'fecha_nacimiento' => $reporte->fecha_nacimiento,
                'edad' => $reporte->edad,
                'sexo' => $reporte->sexo,
                'toma_muestra' => $reporte->toma_muestra,
                'fecha_reporte' => $reporte->fecha_reporte,
                'fecha_validacion' => $reporte->fecha_validacion,
                'medico_solicitante' => $reporte->medico_solicitante,
                'medico_id' => $reporte->medico_id,
                'aplica_iva' => (bool) $reporte->aplica_iva,
                'porcentaje_iva' => (float) config('facturacion.iva'),
                'created_at' => $reporte->created_at,
                'estudios' => $reporte->estudios->map(function ($reporteEstudio) {
                    return [
                        'id' => $reporteEstudio->id,
                        'estudio_id' => $reporteEstudio->estudio_id,
                        'orden' => $reporteEstudio->orden,
                        'nombre' => $reporteEstudio->estudio->nombre,
                        'precio' => $reporteEstudio->precio,
                        'elaboro' => $reporteEstudio->elaboro,
                        'valido' => $reporteEstudio->valido,
                        'tipo_muestra' => $reporteEstudio->tipo_muestra,
                        'metodo' => $reporteEstudio->metodo,
                        'leyenda' => $reporteEstudio->estudio->leyenda,
                        'observaciones' => $reporteEstudio->observaciones,
                        'resultados' => $reporteEstudio->resultados->map(function ($resultado) {
                            return [
                                'id' => $resultado->id,
                                'examen_id' => $resultado->examen_id,
                                'nombre_examen' => $resultado->examen->nombre_examen,
                                'resultado' => $resultado->resultado,
                                'unidad' => $resultado->examen->unidad,
                                'valor_referencia' => $resultado->examen->valor_referencia,
                                'fuera_rango' => $resultado->fuera_rango,
                                'direccion_rango' => $resultado->direccion_rango,
                            ];
                        })
                    ];
                })
            ]
        ]);
    }



}