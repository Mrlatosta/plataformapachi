<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reporte;
use App\Models\ReporteEstudio;
use App\Models\Resultado;
use Barryvdh\DomPDF\Facade\Pdf;


class ReporteController extends Controller
{

public function store(Request $request)
{
    // Generar folio único
    $ultimoFolio = Reporte::orderBy('id', 'desc')->value('folio');
    $numero = $ultimoFolio ? (int) preg_replace('/\D/', '', $ultimoFolio) + 1 : 1;
    $folio = 'RPT-' . str_pad($numero, 4, '0', STR_PAD_LEFT);

    $cliente = $request->input('cliente');

    // Crear reporte
    $reporte = Reporte::create([
        'folio' => $folio,
        'nombre_cliente' => $cliente['nombre'] ?? null,
        'email' => $cliente['email'] ?? null,
        'fecha_nacimiento' => $cliente['fecha_nacimiento'] ?? null,
        'edad' => $cliente['edad'] ?? null,
        'sexo' => $cliente['sexo'] ?? null,
        'toma_muestra' => $request->toma_muestra ?? null,
        'fecha_reporte' => $request->fecha_reporte ?? null,
        'fecha_validacion' => $request->fecha_validacion ?? null,
        'medico_solicitante' => $request->medico_solicitante ?? null,
    ]);

    // Crear estudios y resultados
    foreach ($request->input('estudios', []) as $estudioData) {
        $reporteEstudio = $reporte->estudios()->create([
            'estudio_id' => $estudioData['id'] ?? $estudioData['estudio_id'] ?? null,

            'tipo_muestra' => $estudioData['tipo_muestra'] ?? null,
            'metodo' => $estudioData['metodo'] ?? null,
            'elaboro' => $estudioData['elaboro'] ?? null,
            'valido' => $estudioData['valido'] ?? null,
            'precio' => $estudioData['precio'] ?? 0,
        ]);

        foreach ($estudioData['examenes'] ?? [] as $resultado) {
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





}