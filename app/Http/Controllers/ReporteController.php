<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reporte;
use App\Models\ReporteEstudio;
use App\Models\Resultado;
use Barryvdh\DomPDF\Facade\Pdf;
use Inertia\Inertia;



class ReporteController extends Controller
{

public function store(Request $request)
{
    // Validar datos antes de guardar
    $validated = $request->validate([
        'cliente.nombre' => 'required|string|max:255',
        'cliente.fecha_nacimiento' => 'required|date',
        'cliente.sexo' => 'required|in:Masculino,Femenino',
        'cliente.email' => 'nullable|email|max:255',
        'cliente.edad' => 'nullable|integer|min:0',
        'toma_muestra' => 'required|date',
        'fecha_reporte' => 'required|date',
        'fecha_validacion' => 'required|date',
        'medico_solicitante' => 'nullable|string|max:255',
        'estudios' => 'required|array|min:1',
        'estudios.*.id' => 'required|exists:estudios,id',
        'estudios.*.tipo_muestra' => 'nullable|string|max:255',
        'estudios.*.metodo' => 'nullable|string|max:255',
        'estudios.*.elaboro' => 'nullable|string|max:255',
        'estudios.*.valido' => 'nullable|string|max:255',
        'estudios.*.precio' => 'required|numeric|min:0',
        'estudios.*.examenes' => 'required|array|min:1',
        'estudios.*.examenes.*.id' => 'required|exists:examenes,id',
        'estudios.*.examenes.*.resultado' => 'nullable|string',
    ], [
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
    ]);

    // Generar folio único
    $ultimoFolio = Reporte::orderBy('id', 'desc')->value('folio');
    $numero = $ultimoFolio ? (int) preg_replace('/\D/', '', $ultimoFolio) + 1 : 1;
    $folio = 'RPT-' . str_pad($numero, 4, '0', STR_PAD_LEFT);

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
    ]);

    // Crear estudios y resultados
    foreach ($validated['estudios'] as $estudioData) {
        $reporteEstudio = $reporte->estudios()->create([
            'estudio_id' => $estudioData['id'],
            'tipo_muestra' => $estudioData['tipo_muestra'] ?? null,
            'metodo' => $estudioData['metodo'] ?? null,
            'elaboro' => $estudioData['elaboro'] ?? null,
            'valido' => $estudioData['valido'] ?? null,
            'precio' => $estudioData['precio'],
        ]);

        foreach ($estudioData['examenes'] as $resultado) {
            $reporteEstudio->resultados()->create([
                'examen_id' => $resultado['id'],
                'resultado' => $resultado['resultado'] ?? null,
                'fuera_rango' => $resultado['fuera_rango'] ?? false,
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

    // Opcional: Guardar el archivo en el servidor
    // Storage::put("ordenes/orden-{$reporte->folio}.pdf", $pdfOrden->output());

    // 🔹 Devolver respuesta con enlace al PDF (abrir en nueva pestaña)
  return response()->json([
    'message' => 'Reporte guardado correctamente',
    'id' => $reporte->id,
    'folio' => $reporte->folio
]);


}



    public function generarPDF($reporteId)
    {
        $reporte = Reporte::with([
    'estudios.estudio',
    'estudios.resultados.examen'
])->findOrFail($reporteId);


        return Pdf::loadView('pdf.reporte_biolab', compact('reporte'))
                ->download("reporte-{$reporte->folio}.pdf");
    }

public function generarOrdenTrabajo($reporteId)
{
    $reporte = Reporte::with(['estudios.estudio'])->findOrFail($reporteId);

    $total = $reporte->estudios->sum('precio'); // 💰 suma todos los precios

    $pdf = Pdf::loadView('pdf.orden_trabajo', compact('reporte', 'total'))
        ->setPaper('A4', 'portrait');

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
        'toma_muestra', 'fecha_reporte', 'fecha_validacion', 'medico_solicitante'
    ]));

    $cliente = $request->input('cliente');
    $reporte->update([
        'nombre_cliente' => $cliente['nombre'],
        'email' => $cliente['email'],
        'fecha_nacimiento' => $cliente['fecha_nacimiento'],
        'edad' => $cliente['edad'],
        'sexo' => $cliente['sexo'],
    ]);

    $idsNuevos = collect($request->input('estudios'))->pluck('id')->filter();
    $reporte->estudios()
        ->whereNotIn('id', $idsNuevos)
        ->each(function ($estudio) {
            $estudio->resultados()->delete();
            $estudio->delete();
        });

   // Actualizar o crear estudios
    foreach ($request->input('estudios', []) as $estudioData) {
    $reporteEstudio = null;

    if (!empty($estudioData['id'])) {
        // Si ya existe, lo buscamos
        $reporteEstudio = $reporte->estudios()->find($estudioData['id']);
    }

    if ($reporteEstudio) {
        // Actualizar
        $reporteEstudio->update([
            'tipo_muestra' => $estudioData['tipo_muestra'] ?? null,
            'metodo' => $estudioData['metodo'] ?? null,
            'elaboro' => $estudioData['elaboro'] ?? null,
            'valido' => $estudioData['valido'] ?? null,
            'precio' => $estudioData['precio'] ?? 0,
        ]);

        foreach ($estudioData['resultados'] ?? [] as $resultadoData) {
            if (!empty($resultadoData['id'])) {
                $resultado = $reporteEstudio->resultados()->find($resultadoData['id']);
                if ($resultado) {
                    $resultado->update([
                        'resultado' => $resultadoData['resultado'] ?? null,
                        'fuera_rango' => $resultadoData['fuera_rango'] ?? false,
                    ]);
                }
            } else {
                // Si no tiene id, es nuevo
                $reporteEstudio->resultados()->create([
                    'examen_id' => $resultadoData['examen_id'] ?? $resultadoData['id'],
                    'resultado' => $resultadoData['resultado'] ?? null,
                    'fuera_rango' => $resultadoData['fuera_rango'] ?? false,
                ]);
            }
        }
    } else {
        // Crear nuevo estudio
        $nuevo = $reporte->estudios()->create([
            'estudio_id' => $estudioData['estudio_id'] ?? $estudioData['id'],
            'tipo_muestra' => $estudioData['tipo_muestra'] ?? null,
            'metodo' => $estudioData['metodo'] ?? null,
            'elaboro' => $estudioData['elaboro'] ?? null,
            'valido' => $estudioData['valido'] ?? null,
            'precio' => $estudioData['precio'] ?? 0,
        ]);

        foreach ($estudioData['resultados'] ?? [] as $r) {
            $nuevo->resultados()->create([
                'examen_id' => $r['examen_id'] ?? $r['id'],
                'resultado' => $r['resultado'] ?? null,
                'fuera_rango' => $r['fuera_rango'] ?? false,
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
                'total_estudios' => $reporte->estudios->count(),
                'total_precio' => $reporte->estudios->sum('precio'),
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
                'created_at' => $reporte->created_at,
                'estudios' => $reporte->estudios->map(function ($reporteEstudio) {
                    return [
                        'id' => $reporteEstudio->id,
                        'nombre' => $reporteEstudio->estudio->nombre,
                        'precio' => $reporteEstudio->precio,
                        'elaboro' => $reporteEstudio->elaboro,
                        'valido' => $reporteEstudio->valido,
                        'tipo_muestra' => $reporteEstudio->tipo_muestra,
                        'metodo' => $reporteEstudio->metodo,
                        'leyenda' => $reporteEstudio->estudio->leyenda,
                        'resultados' => $reporteEstudio->resultados->map(function ($resultado) {
                            return [
                                'id' => $resultado->id,
                                'nombre_examen' => $resultado->examen->nombre_examen,
                                'resultado' => $resultado->resultado,
                                'unidad' => $resultado->examen->unidad,
                                'valor_referencia' => $resultado->examen->valor_referencia,
                                'fuera_rango' => $resultado->fuera_rango,
                            ];
                        })
                    ];
                })
            ]
        ]);
    }



}