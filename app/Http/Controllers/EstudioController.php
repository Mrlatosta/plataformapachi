<?php

namespace App\Http\Controllers;

use App\Models\Estudio;
use App\Models\Examen;
use Illuminate\Http\Request;

class EstudioController extends Controller
{
    private function reglasEstudio(): array
    {
        return [
            'nombre' => 'required|string|max:255',
            'leyenda' => 'nullable|string',
            'tipo_muestra' => 'nullable|string|max:255',
            'metodo' => 'nullable|string|max:255',
            'tiempo_entrega' => 'nullable|string|max:255',
            'precio' => 'nullable|numeric|min:0',
            'costo' => 'nullable|numeric|min:0',
            'visible_publico' => 'nullable|boolean',
            'examenes' => 'nullable|array',
            // T5: tabla de niveles de referencia por examen
            'examenes.*.niveles' => 'nullable|array|max:20',
            'examenes.*.niveles.*.desde' => 'nullable|numeric',
            'examenes.*.niveles.*.hasta' => 'nullable|numeric',
            'examenes.*.niveles.*.interpretacion' => 'required|string|max:120',
            'examenes.*.niveles.*.es_normal' => 'nullable|boolean',
        ];
    }

    private function mensajesEstudio(): array
    {
        return [
            'examenes.*.niveles.*.interpretacion.required' => 'Cada nivel de referencia necesita su interpretación (p. ej. Normal, Elevado).',
            'examenes.*.niveles.*.desde.numeric' => 'El "desde" de un nivel debe ser un número.',
            'examenes.*.niveles.*.hasta.numeric' => 'El "hasta" de un nivel debe ser un número.',
        ];
    }

    /**
     * Reemplaza la tabla de niveles del examen por la que viene del catalogo,
     * respetando el orden en que se acomodaron. Solo un nivel puede ser el normal.
     */
    private function guardarNiveles(Examen $examen, ?array $niveles): void
    {
        if ($niveles === null) {
            return; // el formulario no mando niveles: no se tocan
        }

        $examen->niveles()->delete();

        $hayNormal = false;
        foreach (array_values($niveles) as $orden => $nivel) {
            $esNormal = ! $hayNormal && filter_var($nivel['es_normal'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $hayNormal = $hayNormal || $esNormal;

            $examen->niveles()->create([
                'orden' => $orden,
                'desde' => ($nivel['desde'] ?? '') === '' ? null : $nivel['desde'],
                'hasta' => ($nivel['hasta'] ?? '') === '' ? null : $nivel['hasta'],
                'interpretacion' => trim($nivel['interpretacion']),
                'es_normal' => $esNormal,
            ]);
        }
    }

    private function datosEstudio(Request $request): array
    {
        return [
            'nombre' => $request->nombre,
            'leyenda' => $request->leyenda ?? null,
            'tipo_muestra' => $request->tipo_muestra ?? null,
            'metodo' => $request->metodo ?? null,
            'tiempo_entrega' => $request->tiempo_entrega ?? null,
            'precio' => $request->precio ?? 0,
            'costo' => $request->costo ?? 0,
            'visible_publico' => $request->boolean('visible_publico'),
        ];
    }

    // Obtener todos los estudios con sus exámenes
    public function index()
    {
        return Estudio::with(['examenes' => function($query) {
            $query->select('id','estudio_id','seccion','orden','nombre_examen','unidad','valor_referencia')
                  ->orderBy('orden', 'asc');
        }, 'examenes.niveles'])
        ->orderBy('nombre', 'asc')
        ->get(['id', 'nombre', 'leyenda', 'tipo_muestra', 'metodo', 'tiempo_entrega', 'precio', 'costo', 'visible_publico']);
    }

    // Crear nuevo estudio con exámenes
    public function store(Request $request)
    {
        $request->validate($this->reglasEstudio(), $this->mensajesEstudio());

        $estudio = Estudio::create($this->datosEstudio($request));

        foreach ($request->examenes ?? [] as $examen) {
            // Ignorar filas vacías (sin nombre de examen)
            if (empty($examen['nombre_examen'])) {
                continue;
            }
            $nuevo = $estudio->examenes()->create([
                'seccion'           => $examen['seccion'] ?? null,
                'orden'             => $examen['orden'] ?? 0,
                'nombre_examen'     => $examen['nombre_examen'],
                'unidad'            => $examen['unidad'] ?? null,
                'valor_referencia'  => $examen['valor_referencia'] ?? null,
            ]);
            $this->guardarNiveles($nuevo, $examen['niveles'] ?? null);
        }

        return response()->json(['message' => 'Estudio creado con éxito']);
    }

    // Actualizar un estudio y sus exámenes
    public function update(Request $request, $id)
    {
        $request->validate($this->reglasEstudio(), $this->mensajesEstudio());

        $estudio = Estudio::findOrFail($id);
        $estudio->update($this->datosEstudio($request));

        // Obtener IDs de los exámenes que vienen en la petición
        $examenesActualizados = collect($request->examenes ?? []);
        $idsExamenesNuevos = $examenesActualizados->pluck('id')->filter();

        // Eliminar solo los exámenes que ya no están en la lista
        $estudio->examenes()->whereNotIn('id', $idsExamenesNuevos)->delete();

        // Actualizar o crear exámenes
        foreach ($examenesActualizados as $examenData) {
            // Ignorar filas vacías (sin nombre de examen)
            if (empty($examenData['nombre_examen'])) {
                continue;
            }
            if (isset($examenData['id']) && $examenData['id']) {
                // Actualizar examen existente
                $examen = $estudio->examenes()->find($examenData['id']);
                if ($examen) {
                    $examen->update([
                        'seccion'           => $examenData['seccion'] ?? null,
                        'orden'             => $examenData['orden'] ?? 0,
                        'nombre_examen'     => $examenData['nombre_examen'],
                        'unidad'            => $examenData['unidad'] ?? null,
                        'valor_referencia'  => $examenData['valor_referencia'] ?? null,
                    ]);
                    $this->guardarNiveles($examen, $examenData['niveles'] ?? null);
                }
            } else {
                // Crear nuevo examen
                $nuevo = $estudio->examenes()->create([
                    'seccion'           => $examenData['seccion'] ?? null,
                    'orden'             => $examenData['orden'] ?? 0,
                    'nombre_examen'     => $examenData['nombre_examen'],
                    'unidad'            => $examenData['unidad'] ?? null,
                    'valor_referencia'  => $examenData['valor_referencia'] ?? null,
                ]);
                $this->guardarNiveles($nuevo, $examenData['niveles'] ?? null);
            }
        }

        return response()->json(['message' => 'Estudio actualizado con éxito']);
    }

    // Cambia solo la casilla "visible en /inicio" desde el catálogo
    public function cambiarVisibilidad(Request $request, $id)
    {
        $validated = $request->validate(['visible_publico' => 'required|boolean']);

        $estudio = Estudio::findOrFail($id);
        $estudio->update(['visible_publico' => $validated['visible_publico']]);

        return response()->json([
            'message' => 'Visibilidad actualizada',
            'visible_publico' => $estudio->visible_publico,
        ]);
    }

    // Obtener un estudio específico
    public function show($id)
    {
        return Estudio::with('examenes.niveles')->findOrFail($id);
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
