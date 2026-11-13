<?php

namespace App\Http\Controllers;

use App\Models\Paciente;
use App\Models\Reporte;
use App\Models\ReporteEstudio;
use App\Models\ResultadoExamen;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PacienteController extends Controller
{
    private function reglas(): array
    {
        return [
            'nombre' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'telefono' => 'nullable|string|max:20',
            'fecha_nacimiento' => 'nullable|date',
            'edad' => 'nullable|integer|min:0',
            'sexo' => 'nullable|in:Masculino,Femenino',
            'direccion' => 'nullable|string|max:500',
            'notas' => 'nullable|string|max:1000',
            'confirmar_duplicado' => 'nullable|boolean',
        ];
    }

    private function mensajes(): array
    {
        return [
            'nombre.required' => 'El nombre del paciente es obligatorio',
            'email.email' => 'El email no tiene un formato válido',
        ];
    }

    /**
     * 409 con el paciente existente si ya hay uno con el mismo nombre y fecha de
     * nacimiento y no se confirmo que es otra persona.
     */
    private function respuestaDuplicado(Request $request, array $datos, ?int $excluirId = null)
    {
        if ($request->boolean('confirmar_duplicado')) {
            return null;
        }

        $existente = Paciente::buscarDuplicado($datos['nombre'], $datos['fecha_nacimiento'] ?? null, $excluirId);
        if (! $existente) {
            return null;
        }

        return response()->json([
            'message' => 'Ya existe un paciente con el mismo nombre y fecha de nacimiento.',
            'duplicado' => $this->resumen($existente),
        ], 409);
    }

    private function resumen(Paciente $paciente): array
    {
        return [
            'id' => $paciente->id,
            'nombre' => $paciente->nombre,
            'fecha_nacimiento' => $paciente->fecha_nacimiento?->format('Y-m-d'),
            'telefono' => $paciente->telefono,
            'email' => $paciente->email,
            'sexo' => $paciente->sexo,
            'edad' => $paciente->fecha_nacimiento ? $paciente->fecha_nacimiento->age : $paciente->edad,
            'ordenes' => $paciente->reportes_count ?? null,
            'ultima_visita' => $paciente->ultima_visita ?? null,
        ];
    }

    public function index()
    {
        return Paciente::withCount('reportes')
            ->withMax('reportes as ultima_visita', 'toma_muestra')
            ->orderBy('nombre', 'asc')
            ->get();
    }

    public function page()
    {
        return Inertia::render('Pacientes');
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->reglas(), $this->mensajes());

        if ($duplicado = $this->respuestaDuplicado($request, $validated)) {
            return $duplicado;
        }

        $paciente = Paciente::create(collect($validated)->except('confirmar_duplicado')->all());

        return response()->json([
            'message' => 'Paciente registrado correctamente',
            'paciente' => $paciente,
        ], 201);
    }

    public function show($id)
    {
        return Paciente::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $paciente = Paciente::findOrFail($id);
        $validated = $request->validate($this->reglas(), $this->mensajes());

        if ($duplicado = $this->respuestaDuplicado($request, $validated, $paciente->id)) {
            return $duplicado;
        }

        $paciente->update(collect($validated)->except('confirmar_duplicado')->all());

        return response()->json([
            'message' => 'Paciente actualizado correctamente',
            'paciente' => $paciente,
        ]);
    }

    public function destroy($id)
    {
        Paciente::findOrFail($id)->delete();

        return response()->json(['message' => 'Paciente eliminado correctamente']);
    }

    /**
     * Sugerencias mientras se escribe el nombre (sin acentos ni mayusculas;
     * cada palabra puede ir en cualquier orden).
     */
    public function buscar(Request $request)
    {
        $termino = trim((string) $request->query('q', ''));

        if (mb_strlen($termino) < 2) {
            return response()->json([]);
        }

        $pacientes = Paciente::buscar($termino)
            ->withCount('reportes')
            ->withMax('reportes as ultima_visita', 'toma_muestra')
            ->orderBy('nombre')
            ->limit(8)
            ->get();

        return response()->json($pacientes->map(fn ($p) => $this->resumen($p)));
    }

    /**
     * T1: resultado anterior de cada analito del paciente (el mas reciente de
     * ordenes validadas), para mostrarlo junto al campo de captura.
     * El frontend lo busca por examen_id y, si no, por nombre del analito.
     */
    public function resultadosAnteriores(Request $request, $id)
    {
        $excluir = $request->integer('excluir_reporte') ?: null;

        $filas = ResultadoExamen::query()
            ->join('reporte_estudio', 'reporte_estudio.id', '=', 'resultado_examen.reporte_estudio_id')
            ->join('reportes', 'reportes.id', '=', 'reporte_estudio.reporte_id')
            ->join('examenes', 'examenes.id', '=', 'resultado_examen.examen_id')
            ->where('reportes.paciente_id', $id)
            ->when($excluir, fn ($q) => $q->where('reportes.id', '!=', $excluir))
            ->whereIn('reporte_estudio.estado', [ReporteEstudio::VALIDADO, ReporteEstudio::ENTREGADO])
            ->where('resultado_examen.resultado', '!=', '')
            ->where('resultado_examen.resultado', 'not like', '%*%')
            ->orderByDesc('reportes.toma_muestra')
            ->orderByDesc('reportes.id')
            ->limit(500)
            ->get([
                'resultado_examen.examen_id',
                'resultado_examen.resultado',
                'resultado_examen.fuera_rango',
                'resultado_examen.direccion_rango',
                'examenes.nombre_examen',
                'examenes.unidad',
                'reportes.toma_muestra',
                'reportes.folio',
                'reportes.id as reporte_id',
            ]);

        // Uno por examen: el mas reciente
        return response()->json($filas->unique('examen_id')->values()->map(fn ($f) => [
            'examen_id' => $f->examen_id,
            'nombre_examen' => $f->nombre_examen,
            'unidad' => $f->unidad,
            'resultado' => $f->resultado,
            'fuera_rango' => (bool) $f->fuera_rango,
            'direccion_rango' => $f->direccion_rango,
            'fecha' => $f->toma_muestra,
            'folio' => $f->folio,
            'reporte_id' => $f->reporte_id,
        ]));
    }

    /**
     * T1: vista "Historial" con todas las ordenes del paciente.
     */
    public function historial($id)
    {
        $paciente = Paciente::findOrFail($id);

        $ordenes = Reporte::with([
            'estudios.estudio:id,nombre',
            'estudios.resultados.examen:id,nombre_examen,unidad,valor_referencia',
            'estudios.validadoPor:id,name',
        ])
            ->where('paciente_id', $paciente->id)
            ->orderByDesc('toma_muestra')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Reporte $reporte) => [
                'id' => $reporte->id,
                'folio' => $reporte->folio,
                'toma_muestra' => $reporte->toma_muestra,
                'medico_solicitante' => $reporte->medico_solicitante,
                'estado' => ReporteController::estadoOrden($reporte->estudios),
                'estudios' => $reporte->estudios->map(fn (ReporteEstudio $re) => [
                    'id' => $re->id,
                    'nombre' => $re->estudio?->nombre,
                    'estado' => $re->estado,
                    'incluir_en_reporte' => $re->incluir_en_reporte !== false,
                    'validado_por' => $re->validadoPor?->name ?? ($re->estaValidado() ? $re->valido : null),
                    'validado_at' => $re->validado_at,
                    'resultados' => $re->resultados
                        ->filter(fn ($r) => trim((string) $r->resultado) !== '' && ! str_contains((string) $r->resultado, '*'))
                        ->values()
                        ->map(fn ($r) => [
                            'nombre' => $r->examen?->nombre_examen,
                            'resultado' => $r->resultado,
                            'unidad' => $r->examen?->unidad,
                            'referencia' => $r->examen?->valor_referencia,
                            'fuera_rango' => (bool) $r->fuera_rango,
                            'direccion_rango' => $r->direccion_rango,
                        ]),
                ]),
            ]);

        return Inertia::render('Pacientes/Historial', [
            'paciente' => [
                ...$paciente->toArray(),
                'fecha_nacimiento' => $paciente->fecha_nacimiento?->format('Y-m-d'),
                'edad' => $paciente->fecha_nacimiento ? $paciente->fecha_nacimiento->age : $paciente->edad,
            ],
            'ordenes' => $ordenes,
        ]);
    }
}
