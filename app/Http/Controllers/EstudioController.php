<?php

namespace App\Http\Controllers;

use App\Models\Estudio;
use App\Models\Examen;
use Illuminate\Http\Request;

class EstudioController extends Controller
{
    // Obtener todos los estudios con sus exámenes
    public function index()
    {
        return Estudio::with('examenes:id,estudio_id,nombre_examen,unidad,valor_referencia')
        ->get(['id', 'nombre', 'leyenda', 'tipo_muestra', 'metodo']);
    }

    // Crear nuevo estudio con exámenes
    public function store(Request $request)
    {
        $estudio = Estudio::create([
            'nombre' => $request->nombre,
            'leyenda' => $request->leyenda ?? null, // Agregar leyenda si se proporciona
            'tipo_muestra' => $request->tipo_muestra ?? null,
            'metodo' => $request->metodo ?? null,
        ]);

        foreach ($request->examenes as $examen) {
            $estudio->examenes()->create([
                'nombre_examen'     => $examen['nombre_examen'],
                'unidad'            => $examen['unidad'],
                'valor_referencia'  => $examen['valor_referencia'],
            ]);
        }

        return response()->json(['message' => 'Estudio creado con éxito']);
    }

    // Actualizar un estudio y sus exámenes
    public function update(Request $request, $id)
    {
        $estudio = Estudio::findOrFail($id);
        $estudio->update([
            'nombre' => $request->nombre,
            'leyenda' => $request->leyenda ?? null, // Actualizar leyenda si se proporciona
            'tipo_muestra' => $request->tipo_muestra ?? null,
            'metodo' => $request->metodo ?? null,
            

        ]);

        // Elimina todos los exámenes actuales
        $estudio->examenes()->delete();

        // Inserta los nuevos
        foreach ($request->examenes as $examen) {
            $estudio->examenes()->create([
                'nombre_examen'     => $examen['nombre_examen'],
                'unidad'            => $examen['unidad'],
                'valor_referencia'  => $examen['valor_referencia'],
            ]);
        }

        return response()->json(['message' => 'Estudio actualizado con éxito']);
    }

    // Obtener un estudio específico
    public function show($id)
    {
        return Estudio::with('examenes')->findOrFail($id);
    }
}
