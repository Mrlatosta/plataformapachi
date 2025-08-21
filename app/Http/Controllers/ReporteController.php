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
    if ($ultimoFolio) {
        $numero = (int) preg_replace('/\D/', '', $ultimoFolio);
        $numero++;
    } else {
        $numero = 1;
    }
    $folio = 'RPT-' . str_pad($numero, 4, '0', STR_PAD_LEFT);

    $cliente = $request->input('cliente');

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

    foreach ($request->input('estudios', []) as $estudioData) {
        $reporteEstudio = $reporte->estudios()->create([
            'estudio_id' => $estudioData['id'],
            'tipo_muestra' => $estudioData['tipo_muestra'] ?? null,
            'metodo' => $estudioData['metodo'] ?? null,
            'elaboro' => $estudioData['elaboro'] ?? null,
            'valido' => $estudioData['valido'] ?? null,
        ]);

        foreach ($estudioData['examenes'] ?? [] as $resultado) {
            $reporteEstudio->resultados()->create([
                'examen_id' => $resultado['id'],
                'resultado' => $resultado['resultado'] ?? null,
                'fuera_rango' => $resultado['fuera_rango'] ?? false,
            ]);
        }
    }

    return response()->json([
        'message' => 'Reporte guardado correctamente',
        'id' => $reporte->id,
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




}