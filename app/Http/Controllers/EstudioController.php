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
        return Estudio::with(['examenes' => function($query) {
            $query->select('id','estudio_id','seccion','orden','nombre_examen','unidad','valor_referencia')
                  ->orderBy('orden', 'asc');
        }])
        ->orderBy('nombre', 'asc')
        ->get(['id', 'nombre', 'leyenda', 'tipo_muestra', 'metodo', 'precio']);
    }

    // Crear nuevo estudio con exámenes
    public function store(Request $request)
    {
        $estudio = Estudio::create([
            'nombre' => $request->nombre,
            'leyenda' => $request->leyenda ?? null, // Agregar leyenda si se proporciona
            'tipo_muestra' => $request->tipo_muestra ?? null,
            'metodo' => $request->metodo ?? null,
            'precio' => $request->precio ?? 0,
        ]);

        foreach ($request->examenes as $examen) {
            $estudio->examenes()->create([
                'seccion'           => $examen['seccion'] ?? null,
                'orden'             => $examen['orden'] ?? 0,
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
            'leyenda' => $request->leyenda ?? null,
            'tipo_muestra' => $request->tipo_muestra ?? null,
            'metodo' => $request->metodo ?? null,
            'precio' => $request->precio ?? 0,
        ]);

        // Obtener IDs de los exámenes que vienen en la petición
        $examenesActualizados = collect($request->examenes);
        $idsExamenesNuevos = $examenesActualizados->pluck('id')->filter();

        // Eliminar solo los exámenes que ya no están en la lista
        $estudio->examenes()->whereNotIn('id', $idsExamenesNuevos)->delete();

        // Actualizar o crear exámenes
        foreach ($request->examenes as $examenData) {
            if (isset($examenData['id']) && $examenData['id']) {
                // Actualizar examen existente
                $examen = $estudio->examenes()->find($examenData['id']);
                if ($examen) {
                    $examen->update([
                        'seccion'           => $examenData['seccion'] ?? null,
                        'orden'             => $examenData['orden'] ?? 0,
                        'nombre_examen'     => $examenData['nombre_examen'],
                        'unidad'            => $examenData['unidad'],
                        'valor_referencia'  => $examenData['valor_referencia'],
                    ]);
                }
            } else {
                // Crear nuevo examen
                $estudio->examenes()->create([
                    'seccion'           => $examenData['seccion'] ?? null,
                    'orden'             => $examenData['orden'] ?? 0,
                    'nombre_examen'     => $examenData['nombre_examen'],
                    'unidad'            => $examenData['unidad'],
                    'valor_referencia'  => $examenData['valor_referencia'],
                ]);
            }
        }

        return response()->json(['message' => 'Estudio actualizado con éxito']);
    }

    // Obtener un estudio específico
    public function show($id)
    {
        return Estudio::with('examenes')->findOrFail($id);
    }

    // Eliminar un estudio y sus exámenes
    public function destroy($id)
    {
        $estudio = Estudio::findOrFail($id);
        
        // Los exámenes se eliminarán automáticamente si tienes cascada en la BD
        // O puedes eliminarlos explícitamente:
        $estudio->examenes()->delete();
        
        // Eliminar el estudio
        $estudio->delete();

        return response()->json(['message' => 'Estudio eliminado con éxito']);
    }
}
