<?php

namespace App\Http\Controllers;

use App\Models\Estudio;
use App\Models\Resultado;
use App\Models\Examen;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class CapturaResultadosController extends Controller
{
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
