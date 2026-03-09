<?php

namespace App\Http\Controllers;

use App\Models\Estudio;
use App\Models\Resultado;
use App\Models\Examen;
use App\Models\Cotizacion;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Inertia\Inertia;

class CapturaResultadosController extends Controller
{
    /**
     * Renderiza la página de captura. Si viene cotizacion_id, precarga los datos.
     */
    public function page(Request $request)
    {
        $cotizacion = null;

        if ($request->has('cotizacion_id')) {
            $cotizacion = Cotizacion::with(['estudios.estudio.examenes', 'paciente'])
                ->find($request->cotizacion_id);
        }

        return Inertia::render('CapturaResultados', [
            'cotizacionPrecargada' => $cotizacion,
        ]);
    }

    public function index()
    {
        return Estudio::with('examenes')->get();
    }

    public function store(Request $request)
{
    $estudioId = $request->estudio_id;
    $cliente = $request->cliente;
    $resultados = $request->resultados;

    foreach ($resultados as $examenId => $valor) {
        Resultado::create([
            'estudio_id' => $estudioId,
            'examen_id' => $examenId,
            'cliente' => $cliente,
            'resultado' => $valor,
        ]);
    }

    return response()->json(['message' => 'Resultados guardados']);
}


    public function generarPDF()
    {
        $estudios = Estudio::with('examenes.resultado')->get();

        $pdf = Pdf::loadView('pdf.resultados', compact('estudios'));
        return $pdf->stream('resultados.pdf');
    }
}
