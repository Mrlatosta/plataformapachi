<?php

namespace App\Http\Controllers;

use App\Models\Estudio;
use App\Models\Jornada;
use App\Models\JornadaParticipante;
use App\Models\Medico;
use App\Models\Reporte;
use App\Support\RangoReferencia;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Captura rápida por jornadas: se predefine un grupo de estudios (p. ej. antidoping)
 * con resultados por defecto y se capturan muchos colaboradores en una sola tabla.
 * Al final cada colaborador se convierte en un Reporte normal (folio, QR y PDF).
 */
class JornadaController extends Controller
{
    private const MENSAJES = [
        'nombre.required' => 'Ponle un nombre a la jornada, por ejemplo "Antidoping Empresa X".',
        'fecha_toma.required' => 'Indica la fecha y hora de la toma de muestra.',
        'estudios.required' => 'Agrega al menos un estudio a la jornada.',
        'estudios.min' => 'Agrega al menos un estudio a la jornada.',
        'estudios.*.estudio_id.exists' => 'Uno de los estudios ya no existe en el catálogo; quítalo y vuelve a agregarlo.',
        'estudios.*.estudio_id.distinct' => 'Hay un estudio repetido en la jornada.',
        'estudios.max' => 'Una jornada puede tener como máximo 15 estudios.',
        'estudios.*.precio.required' => 'Cada estudio necesita un precio.',
        'estudios.*.precio.numeric' => 'El precio de cada estudio debe ser un número.',
        'estudios.*.precio.min' => 'El precio no puede ser negativo.',
        'estudios.*.*.max' => 'Uno de los textos del estudio es demasiado largo.',
        'fecha_toma.date' => 'La fecha de la toma no es válida.',
        'medico_id.exists' => 'El médico elegido ya no existe; elige otro.',
        'nombre.max' => 'El nombre es demasiado largo.',
        'empresa.max' => 'El nombre de la empresa es demasiado largo.',
        'notas.max' => 'Las notas son demasiado largas.',
    ];

    // Mismos mensajes para la lista (participantes.*.campo) y para editar a uno (campo)
    private const MENSAJES_PARTICIPANTES = [
        'participantes.required' => 'Agrega al menos un colaborador.',
        'participantes.min' => 'Agrega al menos un colaborador.',
        'participantes.max' => 'Se pueden agregar como máximo 500 colaboradores a la vez; divide la lista en partes.',
        'participantes.*.nombre.required' => 'Cada colaborador necesita un nombre.',
        'participantes.*.nombre.max' => 'Uno de los nombres es demasiado largo.',
        'participantes.*.sexo.in' => 'El sexo debe ser Hombre o Mujer.',
        'participantes.*.fecha_nacimiento.date_format' => 'La fecha de nacimiento debe ser una fecha válida.',
        'participantes.*.fecha_nacimiento.before_or_equal' => 'La fecha de nacimiento no puede ser futura.',
        'participantes.*.edad.*' => 'La edad debe ser un número entre 0 y 120.',
        'nombre.required' => 'El nombre del colaborador no puede quedar vacío.',
        'nombre.max' => 'El nombre es demasiado largo.',
        'sexo.in' => 'El sexo debe ser Hombre o Mujer.',
        'fecha_nacimiento.date_format' => 'La fecha de nacimiento debe ser una fecha válida.',
        'fecha_nacimiento.before_or_equal' => 'La fecha de nacimiento no puede ser futura.',
        'edad.*' => 'La edad debe ser un número entre 0 y 120.',
        'resultados.*.max' => 'Cada resultado puede tener como máximo 255 caracteres.',
        'estado.in' => 'Estado no válido.',
    ];

    public function page()
    {
        $jornadas = Jornada::withCount([
            'participantes',
            'participantes as listos_count' => fn ($q) => $q->where('estado', JornadaParticipante::LISTO)->whereNull('reporte_id'),
            'participantes as reportados_count' => fn ($q) => $q->whereNotNull('reporte_id'),
        ])
            ->orderByDesc('fecha_toma')
            ->orderByDesc('id')
            ->get();

        $nombres = Estudio::whereIn('id', $jornadas->flatMap(fn ($j) => collect($j->estudios)->pluck('estudio_id'))->unique())
            ->pluck('nombre', 'id');

        return Inertia::render('CapturaRapida/Index', [
            'jornadas' => $jornadas->map(fn (Jornada $j) => [
                'id' => $j->id,
                'nombre' => $j->nombre,
                'empresa' => $j->empresa,
                'fecha_toma' => $j->fecha_toma->format('Y-m-d\TH:i'),
                'estudios' => collect($j->estudios)->map(fn ($e) => $nombres[$e['estudio_id']] ?? 'Estudio eliminado')->values(),
                'participantes' => $j->participantes_count,
                'listos' => $j->listos_count,
                'reportados' => $j->reportados_count,
            ]),
            ...$this->catalogos(),
        ]);
    }

    public function show(int $id)
    {
        $jornada = Jornada::findOrFail($id);

        return Inertia::render('CapturaRapida/Jornada', [
            'jornada' => $this->jornadaParaVista($jornada),
            'participantes' => $this->participantes($jornada),
            ...$this->catalogos(),
        ]);
    }

    public function store(Request $request)
    {
        $jornada = new Jornada(['creado_por' => $request->user()?->id]);
        $jornada->fill($this->validarJornada($request))->save();

        return response()->json(['message' => 'Jornada creada', 'id' => $jornada->id], 201);
    }

    public function update(Request $request, int $id)
    {
        $jornada = Jornada::findOrFail($id);
        $jornada->update($this->validarJornada($request));

        return response()->json([
            'message' => 'Jornada actualizada',
            'jornada' => $this->jornadaParaVista($jornada->fresh()),
        ]);
    }

    public function destroy(int $id)
    {
        // Los reportes ya emitidos se conservan; solo se borra la lista de captura
        Jornada::findOrFail($id)->delete();

        return response()->json(['message' => 'Jornada eliminada']);
    }

    /**
     * Agrega uno o varios colaboradores (captura uno a uno o lista pegada de Excel).
     */
    public function agregarParticipantes(Request $request, int $id)
    {
        $jornada = Jornada::findOrFail($id);

        $validado = $request->validate([
            'participantes' => 'required|array|min:1|max:500',
            'participantes.*.nombre' => 'required|string|max:255',
            'participantes.*.sexo' => ['nullable', Rule::in(['Masculino', 'Femenino'])],
            'participantes.*.fecha_nacimiento' => 'nullable|date_format:Y-m-d|before_or_equal:today',
            'participantes.*.edad' => 'nullable|integer|min:0|max:120',
        ], self::MENSAJES_PARTICIPANTES);

        $creados = DB::transaction(fn () => collect($validado['participantes'])->map(
            fn ($datos) => $jornada->participantes()->create($this->datosPersonales($jornada, $datos))
        ));

        return response()->json([
            'message' => $creados->count() === 1 ? 'Colaborador agregado' : "{$creados->count()} colaboradores agregados",
            'participantes' => $creados->map(fn ($p) => $this->participanteParaVista($p->setRelation('reporte', null))),
        ], 201);
    }

    /**
     * Cambios parciales: datos personales, resultados o estado (pendiente / listo).
     */
    public function actualizarParticipante(Request $request, int $id, int $participanteId)
    {
        $jornada = Jornada::findOrFail($id);
        $participante = $jornada->participantes()->with('reporte:id,folio')->findOrFail($participanteId);
        $this->exigirSinReporte($participante);

        $validado = $request->validate([
            'nombre' => 'sometimes|required|string|max:255',
            'sexo' => ['sometimes', 'nullable', Rule::in(['Masculino', 'Femenino'])],
            'fecha_nacimiento' => 'sometimes|nullable|date_format:Y-m-d|before_or_equal:today',
            'edad' => 'sometimes|nullable|integer|min:0|max:120',
            'resultados' => 'sometimes|array',
            'resultados.*' => 'nullable|string|max:255',
            'estado' => ['sometimes', Rule::in([JornadaParticipante::PENDIENTE, JornadaParticipante::LISTO])],
        ], self::MENSAJES_PARTICIPANTES);

        $personales = array_intersect_key($validado, array_flip(['nombre', 'sexo', 'fecha_nacimiento', 'edad']));
        if ($personales) {
            $participante->fill($this->datosPersonales($jornada, $personales + [
                'nombre' => $participante->nombre,
                'sexo' => $participante->sexo,
                'fecha_nacimiento' => $participante->fecha_nacimiento?->toDateString(),
                'edad' => array_key_exists('fecha_nacimiento', $personales) ? null : $participante->edad,
            ]));
        }

        if (array_key_exists('resultados', $validado)) {
            $participante->resultados = $validado['resultados'];
        }

        if (isset($validado['estado'])) {
            if ($validado['estado'] === JornadaParticipante::LISTO && $faltan = $participante->datosFaltantes()) {
                throw ValidationException::withMessages([
                    'estado' => "A {$participante->nombre} le falta " . implode(' y ', $faltan) . ' para marcarlo como listo.',
                ]);
            }
            $participante->estado = $validado['estado'];
            $participante->capturado_en = $validado['estado'] === JornadaParticipante::LISTO ? now() : null;
        }

        $participante->save();

        return response()->json(['participante' => $this->participanteParaVista($participante)]);
    }

    public function eliminarParticipante(int $id, int $participanteId)
    {
        $participante = JornadaParticipante::where('jornada_id', $id)->with('reporte:id,folio')->findOrFail($participanteId);
        $this->exigirSinReporte($participante);
        $participante->delete();

        return response()->json(['message' => 'Colaborador eliminado']);
    }

    /**
     * Emite un Reporte por cada colaborador listo que aún no tiene uno.
     */
    public function generarReportes(Request $request, int $id)
    {
        $validado = $request->validate([
            'participantes' => 'nullable|array',
            'participantes.*' => 'integer',
        ]);

        $jornada = Jornada::findOrFail($id);
        $estudios = $this->estudiosConfigurados($jornada);
        if ($estudios->isEmpty()) {
            throw ValidationException::withMessages(['estudios' => 'La jornada no tiene estudios vigentes en el catálogo.']);
        }

        $pendientes = $jornada->participantes()
            ->where('estado', JornadaParticipante::LISTO)
            ->whereNull('reporte_id')
            // Sin lista se emiten todos los listos; una lista vacía no emite ninguno
            ->when(isset($validado['participantes']), fn ($q) => $q->whereIn('id', $validado['participantes']))
            ->orderBy('id')
            ->get();

        $generados = 0;
        $omitidos = [];
        foreach ($pendientes as $participante) {
            if ($faltan = $participante->datosFaltantes()) {
                $omitidos[] = "{$participante->nombre}: falta " . implode(' y ', $faltan);
                continue;
            }

            // Uno por uno: si algo falla a la mitad, lo ya emitido se conserva
            DB::transaction(fn () => $this->emitirReporte($jornada, $estudios, $participante));
            $generados++;
        }

        return response()->json([
            'message' => $generados === 1 ? 'Se generó 1 reporte' : "Se generaron {$generados} reportes",
            'generados' => $generados,
            'omitidos' => $omitidos,
            'participantes' => $this->participantes($jornada),
        ]);
    }

    /**
     * Resumen en CSV (abre en Excel) para entregar a la empresa.
     */
    public function exportarCsv(int $id)
    {
        $jornada = Jornada::findOrFail($id);
        $estudios = $this->estudiosConfigurados($jornada);
        $examenes = $estudios->flatMap(fn ($e) => $e['examenes']);
        $participantes = $jornada->participantes()->with('reporte:id,folio')->orderBy('id')->get();

        $nombreArchivo = trim(preg_replace('/[\\\\\/:*?"<>|\x00-\x1F]+/u', ' ', $jornada->nombre)) . '.csv';

        return response()->streamDownload(function () use ($participantes, $examenes) {
            $salida = fopen('php://output', 'w');
            fwrite($salida, "\xEF\xBB\xBF"); // BOM para que Excel respete los acentos

            fputcsv($salida, [
                '#', 'Nombre', 'Sexo', 'Edad', 'Fecha de nacimiento',
                ...$examenes->pluck('nombre_examen'),
                'Fuera de rango', 'Estado', 'Folio',
            ]);

            foreach ($participantes as $indice => $p) {
                $valores = $examenes->map(fn ($ex) => $this->resultadoDe($p, $ex));
                $fuera = $examenes->filter(fn ($ex) => RangoReferencia::fueraDeRango($this->resultadoDe($p, $ex), $ex->valor_referencia));

                fputcsv($salida, [
                    $indice + 1,
                    $p->nombre,
                    $p->sexo,
                    $p->edad,
                    $p->fecha_nacimiento?->format('d/m/Y'),
                    ...$valores,
                    $fuera->pluck('nombre_examen')->implode(', ') ?: 'Ninguno',
                    $p->reporte ? 'Reporte emitido' : ($p->estado === JornadaParticipante::LISTO ? 'Capturado' : 'Pendiente'),
                    $p->reporte?->folio,
                ]);
            }

            fclose($salida);
        }, $nombreArchivo, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // ------------------------------------------------------------------

    private function catalogos(): array
    {
        return [
            'catalogo' => Estudio::with(['examenes' => fn ($q) => $q
                ->select('id', 'estudio_id', 'nombre_examen', 'unidad', 'valor_referencia', 'orden')
                ->orderBy('orden')->orderBy('id')])
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'tipo_muestra', 'metodo', 'precio']),
            'medicos' => Medico::where('activo', true)->orderBy('nombre')->get(['id', 'nombre', 'especialidad']),
        ];
    }

    private function validarJornada(Request $request): array
    {
        $validado = $request->validate([
            'nombre' => 'required|string|max:255',
            'empresa' => 'nullable|string|max:255',
            'fecha_toma' => 'required|date',
            'medico_id' => 'nullable|integer|exists:medicos,id',
            'medico_solicitante' => 'nullable|string|max:255',
            'aplica_iva' => 'boolean',
            'notas' => 'nullable|string|max:2000',
            'estudios' => 'required|array|min:1|max:15',
            'estudios.*.estudio_id' => 'required|integer|distinct|exists:estudios,id',
            'estudios.*.tipo_muestra' => 'nullable|string|max:255',
            'estudios.*.metodo' => 'nullable|string|max:255',
            'estudios.*.elaboro' => 'nullable|string|max:255',
            'estudios.*.valido' => 'nullable|string|max:255',
            'estudios.*.precio' => 'required|numeric|min:0',
            'estudios.*.predeterminados' => 'nullable|array',
            'estudios.*.predeterminados.*' => 'nullable|string|max:255',
        ], self::MENSAJES);

        // El nombre del médico registrado manda sobre el texto libre
        if (! empty($validado['medico_id'])) {
            $validado['medico_solicitante'] = Medico::whereKey($validado['medico_id'])->value('nombre');
        }

        $validado['aplica_iva'] = $request->boolean('aplica_iva');
        $validado['estudios'] = collect($validado['estudios'])->map(fn ($e) => [
            'estudio_id' => (int) $e['estudio_id'],
            'tipo_muestra' => $e['tipo_muestra'] ?? null,
            'metodo' => $e['metodo'] ?? null,
            'elaboro' => $e['elaboro'] ?? null,
            'valido' => $e['valido'] ?? null,
            'precio' => (float) $e['precio'],
            // "Vacío" llega como null y se guarda como "" para distinguirlo de "sin configurar"
            'predeterminados' => (object) array_map(fn ($v) => (string) ($v ?? ''), $e['predeterminados'] ?? []),
        ])->values()->all();

        return $validado;
    }

    /**
     * Configuración de la jornada combinada con el catálogo vigente: cada estudio
     * con sus exámenes y el resultado predeterminado de cada uno.
     */
    private function estudiosConfigurados(Jornada $jornada): Collection
    {
        $config = collect($jornada->estudios);
        $catalogo = Estudio::with(['examenes' => fn ($q) => $q->orderBy('orden')->orderBy('id')])
            ->whereIn('id', $config->pluck('estudio_id'))
            ->get()
            ->keyBy('id');

        return $config
            ->filter(fn ($e) => $catalogo->has($e['estudio_id']))
            ->map(function ($e) use ($catalogo) {
                $estudio = $catalogo[$e['estudio_id']];
                $predeterminados = (array) ($e['predeterminados'] ?? []);

                $examenes = $estudio->examenes->each(function ($examen) use ($predeterminados) {
                    // Sin valor configurado, los cualitativos arrancan en su valor normal
                    $examen->predeterminado = array_key_exists($examen->id, $predeterminados)
                        ? (string) $predeterminados[$examen->id]
                        : (RangoReferencia::opcionesCualitativas($examen->valor_referencia)[0] ?? '');
                });

                return [...$e, 'nombre' => $estudio->nombre, 'examenes' => $examenes];
            })
            ->values();
    }

    private function jornadaParaVista(Jornada $jornada): array
    {
        return [
            'id' => $jornada->id,
            'nombre' => $jornada->nombre,
            'empresa' => $jornada->empresa,
            'fecha_toma' => $jornada->fecha_toma->format('Y-m-d\TH:i'),
            'medico_id' => $jornada->medico_id,
            'medico_solicitante' => $jornada->medico_solicitante,
            'aplica_iva' => $jornada->aplica_iva,
            'notas' => $jornada->notas,
            'estudios' => $this->estudiosConfigurados($jornada)->map(fn ($e) => [
                ...$e,
                'examenes' => $e['examenes']->map(fn ($ex) => [
                    'id' => $ex->id,
                    'nombre_examen' => $ex->nombre_examen,
                    'unidad' => $ex->unidad,
                    'valor_referencia' => $ex->valor_referencia,
                    'predeterminado' => $ex->predeterminado,
                ])->values(),
            ]),
        ];
    }

    private function participantes(Jornada $jornada): Collection
    {
        return $jornada->participantes()
            ->with('reporte:id,folio')
            ->orderBy('id')
            ->get()
            ->map(fn ($p) => $this->participanteParaVista($p));
    }

    private function participanteParaVista(JornadaParticipante $p): array
    {
        return [
            'id' => $p->id,
            'nombre' => $p->nombre,
            'sexo' => $p->sexo,
            'fecha_nacimiento' => $p->fecha_nacimiento?->toDateString(),
            'edad' => $p->edad,
            'resultados' => (object) ($p->resultados ?? []),
            'estado' => $p->estado,
            'reporte' => $p->reporte ? ['id' => $p->reporte->id, 'folio' => $p->reporte->folio] : null,
        ];
    }

    /**
     * Nombre limpio y edad calculada a la fecha de la toma cuando hay fecha de nacimiento.
     */
    private function datosPersonales(Jornada $jornada, array $datos): array
    {
        $fecha = $datos['fecha_nacimiento'] ?? null;
        $edad = $datos['edad'] ?? null;

        if ($fecha) {
            $edad = (int) Carbon::parse($fecha)->diffInYears($jornada->fecha_toma);
        }

        return [
            'nombre' => trim(preg_replace('/\s+/u', ' ', $datos['nombre'])),
            'sexo' => $datos['sexo'] ?? null,
            'fecha_nacimiento' => $fecha,
            'edad' => $edad,
        ];
    }

    private function exigirSinReporte(JornadaParticipante $participante): void
    {
        if ($participante->reporte_id) {
            $folio = $participante->reporte?->folio ?? 'emitido';
            throw ValidationException::withMessages([
                'participante' => "{$participante->nombre} ya tiene el reporte {$folio}; corrígelo desde Lista de reportes.",
            ]);
        }
    }

    private function resultadoDe(JornadaParticipante $participante, $examen): string
    {
        // Un resultado vaciado a propósito llega como null y no debe volver al predeterminado
        $resultados = $participante->resultados ?? [];

        return (string) (array_key_exists($examen->id, $resultados) ? $resultados[$examen->id] : $examen->predeterminado);
    }

    private function emitirReporte(Jornada $jornada, Collection $estudios, JornadaParticipante $participante): void
    {
        // Las fechas de los reportes se guardan como hora local de Cancún
        $ahora = now(config('app.local_timezone'))->format('Y-m-d H:i:s');

        $reporte = Reporte::create([
            'folio' => Reporte::siguienteFolio(),
            'nombre_cliente' => $participante->nombre,
            'fecha_nacimiento' => $participante->fecha_nacimiento,
            'edad' => $participante->edad,
            'sexo' => $participante->sexo,
            'toma_muestra' => $jornada->fecha_toma,
            'fecha_reporte' => $ahora,
            'fecha_validacion' => $ahora,
            'medico_solicitante' => $jornada->medico_solicitante,
            'medico_id' => $jornada->medico_id,
            'aplica_iva' => $jornada->aplica_iva,
            'descuento' => 0,
        ]);

        foreach ($estudios as $orden => $estudio) {
            $reporteEstudio = $reporte->estudios()->create([
                'estudio_id' => $estudio['estudio_id'],
                'orden' => $orden,
                // En algunas bases estas columnas no aceptan null: vacío se guarda como ""
                'tipo_muestra' => $estudio['tipo_muestra'] ?? '',
                'metodo' => $estudio['metodo'] ?? '',
                'elaboro' => $estudio['elaboro'] ?? '',
                'valido' => $estudio['valido'] ?? '',
                'precio' => $estudio['precio'],
            ]);

            foreach ($estudio['examenes'] as $examen) {
                $valor = $this->resultadoDe($participante, $examen);
                $fueraRango = RangoReferencia::fueraDeRango($valor, $examen->valor_referencia);

                $reporteEstudio->resultados()->create([
                    'examen_id' => $examen->id,
                    // La columna no acepta null: sin resultado se guarda ""
                    'resultado' => $valor,
                    'fuera_rango' => $fueraRango,
                    'direccion_rango' => $fueraRango ? RangoReferencia::direccion($valor, $examen->valor_referencia) : null,
                ]);
            }
        }

        $participante->update(['reporte_id' => $reporte->id]);
    }
}
