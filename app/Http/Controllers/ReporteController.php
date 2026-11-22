<?php

namespace App\Http\Controllers;

use App\Models\CotizacionEstudio;
use App\Models\Cotizacion;
use App\Models\Estudio;
use App\Models\Examen;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\Reporte;
use App\Models\ReporteEstudio;
use App\Models\ResultadoExamen;
use App\Models\User;
use App\Services\Comisiones;
use App\Services\ReportePdf;
use App\Support\QrVerificacion;
use App\Support\RangoReferencia;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ReporteController extends Controller
{
    /** Cache de examenes (referencia y niveles) para no consultar el mismo varias veces */
    private array $examenes = [];

    private function examen($examenId): ?Examen
    {
        if (! array_key_exists($examenId, $this->examenes)) {
            $this->examenes[$examenId] = Examen::with('niveles')->find($examenId);
        }

        return $this->examenes[$examenId];
    }

    /**
     * Direccion ('alto' / 'bajo' / null) de un resultado marcado como fuera de rango.
     * Respeta la seleccion del laboratorio y, si no viene, la deduce con la
     * referencia que le toca al paciente (sexo, edad, nivel normal).
     */
    private function resolverDireccionRango($examenId, $resultado, bool $fueraRango, $direccionManual = null, $sexo = null, $edad = null): ?string
    {
        if (! $fueraRango) {
            return null;
        }

        $direccion = strtolower((string) $direccionManual);
        if (in_array($direccion, [RangoReferencia::ALTO, RangoReferencia::BAJO], true)) {
            return $direccion;
        }

        $examen = $this->examen($examenId);

        return $examen
            ? RangoReferencia::direccion($resultado, $examen->valor_referencia, $sexo, $edad, $examen->niveles)
            : null;
    }

    private function esAdmin(Request $request): bool
    {
        return (bool) $request->user()?->esAdmin();
    }

    /**
     * Reglas compartidas por el guardado y la previsualizacion, para que la vista
     * previa refleje exactamente lo que se va a guardar.
     * Recepcion no captura precios ni fecha de validacion: los pone el sistema.
     */
    private function reglasReporte(bool $admin): array
    {
        return [
            'paciente_id' => 'nullable|integer|exists:pacientes,id',
            'cotizacion_id' => 'nullable|integer|exists:cotizaciones,id',
            'cliente.nombre' => 'required|string|max:255',
            'cliente.fecha_nacimiento' => 'required|date',
            'cliente.sexo' => 'required|in:Masculino,Femenino',
            'cliente.email' => 'nullable|email|max:255',
            'cliente.telefono' => 'nullable|string|max:20',
            'cliente.edad' => 'nullable|integer|min:0',
            'toma_muestra' => 'required|date',
            'fecha_reporte' => 'required|date',
            'fecha_validacion' => $admin ? 'required|date' : 'nullable|date',
            'medico_solicitante' => 'nullable|string|max:255',
            'medico_id' => 'nullable|exists:medicos,id',
            'aplica_iva' => 'nullable|boolean',
            'descuento' => 'nullable|numeric|min:0|max:100',
            'estudios' => 'required|array|min:1',
            'estudios.*.id' => 'required|exists:estudios,id',
            'estudios.*.orden' => 'nullable|integer',
            'estudios.*.tipo_muestra' => 'nullable|string|max:255',
            'estudios.*.metodo' => 'nullable|string|max:255',
            'estudios.*.elaboro' => 'nullable|string|max:255',
            'estudios.*.valido' => 'nullable|string|max:255',
            'estudios.*.precio' => $admin ? 'required|numeric|min:0' : 'nullable|numeric|min:0',
            'estudios.*.observaciones' => 'nullable|string',
            'estudios.*.incluir_en_reporte' => 'nullable|boolean',
            'estudios.*.examenes' => 'required|array|min:1',
            'estudios.*.examenes.*.id' => 'required|exists:examenes,id',
            'estudios.*.examenes.*.resultado' => 'nullable|string',
            'estudios.*.examenes.*.fuera_rango' => 'nullable|boolean',
            'estudios.*.examenes.*.direccion_rango' => 'nullable|in:alto,bajo',
        ];
    }

    private function mensajesReporte(): array
    {
        return [
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
        ];
    }

    /**
     * Precio de cada estudio (por estudio_id) y descuento que se guardan.
     * Recepcion no puede cambiar precios: se toman de la cotizacion de origen
     * o del catalogo, sin descuento.
     */
    private function preciosYDescuento(Request $request, array $validated): array
    {
        $estudioIds = collect($validated['estudios'])->pluck('id');

        if ($this->esAdmin($request)) {
            return [
                collect($validated['estudios'])->mapWithKeys(fn ($e) => [$e['id'] => (float) $e['precio']])->all(),
                (float) ($validated['descuento'] ?? 0),
            ];
        }

        $precios = Estudio::whereIn('id', $estudioIds)->pluck('precio', 'id')->map(fn ($p) => (float) $p)->all();
        $descuento = 0.0;

        if (! empty($validated['cotizacion_id'])) {
            $cotizacion = Cotizacion::find($validated['cotizacion_id']);
            $descuento = (float) ($cotizacion?->descuento ?? 0);
            CotizacionEstudio::where('cotizacion_id', $validated['cotizacion_id'])
                ->whereIn('estudio_id', $estudioIds)
                ->get()
                ->each(function ($ce) use (&$precios) {
                    $precios[$ce->estudio_id] = (float) $ce->precio;
                });
        }

        return [$precios, $descuento];
    }

    /**
     * T1: liga la orden con su paciente. Si no se eligio uno de la lista se busca
     * por nombre + fecha de nacimiento y, si no existe, se da de alta.
     */
    private function resolverPaciente(array $cliente, $pacienteId): Paciente
    {
        $paciente = $pacienteId ? Paciente::find($pacienteId) : null;
        $paciente ??= Paciente::buscarDuplicado($cliente['nombre'], $cliente['fecha_nacimiento'] ?? null);

        if (! $paciente) {
            return Paciente::create([
                'nombre' => $cliente['nombre'],
                'fecha_nacimiento' => $cliente['fecha_nacimiento'] ?? null,
                'sexo' => $cliente['sexo'] ?? null,
                'email' => $cliente['email'] ?? null,
                'telefono' => $cliente['telefono'] ?? null,
                'edad' => $cliente['edad'] ?? null,
            ]);
        }

        // Completa datos que el paciente no tenia, sin pisar los existentes
        $faltantes = collect([
            'fecha_nacimiento' => $cliente['fecha_nacimiento'] ?? null,
            'sexo' => $cliente['sexo'] ?? null,
            'email' => $cliente['email'] ?? null,
            'telefono' => $cliente['telefono'] ?? null,
        ])->filter(fn ($valor, $campo) => $valor && empty($paciente->{$campo}));

        if ($faltantes->isNotEmpty()) {
            $paciente->update($faltantes->all());
        }

        return $paciente;
    }

    /**
     * Estado y firmas de un estudio cuyos resultados se acaban de capturar.
     * Lo que captura el administrador (Q.F.B.) queda validado; lo de Recepcion,
     * pendiente de validar.
     */
    private function datosDeCaptura(User $usuario, iterable $resultados, ?string $valido): array
    {
        $estado = ReporteEstudio::estadoSinValidar($resultados);
        $hayResultados = $estado === ReporteEstudio::CAPTURADO;
        $valida = $usuario->esAdmin() && $hayResultados;

        return [
            'estado' => $valida ? ReporteEstudio::VALIDADO : $estado,
            'capturado_por' => $hayResultados ? $usuario->id : null,
            'capturado_at' => $hayResultados ? now() : null,
            'validado_por' => $valida ? $usuario->id : null,
            'validado_at' => $valida ? now() : null,
            'entregado_at' => null,
            // Recepcion no firma: "Validó" se llena al validar
            'valido' => $usuario->esAdmin() ? ($valido ?: config('laboratorio.responsable')) : '',
        ];
    }

    /** T12: el nombre del medico sale de la lista; sin medico es "A quien corresponda". */
    private function nombreMedico($medicoId): ?string
    {
        return $medicoId ? Medico::whereKey($medicoId)->value('nombre') : null;
    }

    /**
     * T13: tipo de muestra y metodo que se guardan para un estudio de la orden.
     * Recepcion no los cambia: se queda lo guardado o, si es nuevo, lo de siempre
     * del catalogo. El administrador elige de la lista; si queda distinto a lo que
     * habia (o a lo de siempre) se registra quien lo cambio y cuando.
     */
    private function muestraYMetodo(array $estudioData, ?Estudio $catalogo, User $usuario, ?ReporteEstudio $actual = null): array
    {
        $deSiempre = fn (string $campo) => $actual
            ? (string) $actual->{$campo}
            : (in_array(trim((string) $catalogo?->{$campo}), ['', 'N/A'], true) ? '' : trim((string) $catalogo->{$campo}));

        if (! $usuario->esAdmin()) {
            return ['tipo_muestra' => $deSiempre('tipo_muestra'), 'metodo' => $deSiempre('metodo')];
        }

        $datos = [
            'tipo_muestra' => trim((string) ($estudioData['tipo_muestra'] ?? '')),
            'metodo' => trim((string) ($estudioData['metodo'] ?? '')),
        ];

        if ($datos['tipo_muestra'] !== $deSiempre('tipo_muestra') || $datos['metodo'] !== $deSiempre('metodo')) {
            $datos['muestra_metodo_cambiado_por'] = $usuario->id;
            $datos['muestra_metodo_cambiado_at'] = now();
        }

        return $datos;
    }

    public function store(Request $request)
    {
        $usuario = $request->user();
        $validated = $request->validate($this->reglasReporte($usuario->esAdmin()), $this->mensajesReporte());
        [$precios, $descuento] = $this->preciosYDescuento($request, $validated);
        $cliente = $validated['cliente'];

        $catalogo = Estudio::whereIn('id', collect($validated['estudios'])->pluck('id'))->get()->keyBy('id');

        $reporte = DB::transaction(function () use ($validated, $cliente, $usuario, $precios, $descuento, $catalogo) {
            $paciente = $this->resolverPaciente($cliente, $validated['paciente_id'] ?? null);

            $reporte = Reporte::create([
                'folio' => Reporte::siguienteFolio(),
                'paciente_id' => $paciente->id,
                'creado_por' => $usuario->id,
                'nombre_cliente' => $cliente['nombre'],
                'email' => $cliente['email'] ?? null,
                'fecha_nacimiento' => $cliente['fecha_nacimiento'],
                'edad' => self::edadPaciente($cliente['edad'] ?? null, $cliente['fecha_nacimiento'], $validated['toma_muestra']),
                'sexo' => $cliente['sexo'],
                'toma_muestra' => $validated['toma_muestra'],
                'fecha_reporte' => $validated['fecha_reporte'],
                // Recepcion: la fecha de validacion se pone cuando el Q.F.B. valida
                'fecha_validacion' => $usuario->esAdmin() ? $validated['fecha_validacion'] : null,
                'medico_solicitante' => $this->nombreMedico($validated['medico_id'] ?? null),
                'medico_id' => $validated['medico_id'] ?? null,
                'aplica_iva' => filter_var($validated['aplica_iva'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'descuento' => $descuento,
            ]);

            foreach ($validated['estudios'] as $indice => $estudioData) {
                $reporteEstudio = $reporte->estudios()->create([
                    'estudio_id' => $estudioData['id'],
                    'orden' => $estudioData['orden'] ?? $indice,
                    ...$this->muestraYMetodo($estudioData, $catalogo->get($estudioData['id']), $usuario),
                    'elaboro' => ($estudioData['elaboro'] ?? '') ?: $usuario->name,
                    'precio' => $precios[$estudioData['id']] ?? 0,
                    'observaciones' => $estudioData['observaciones'] ?? null,
                    'incluir_en_reporte' => filter_var($estudioData['incluir_en_reporte'] ?? true, FILTER_VALIDATE_BOOLEAN),
                    ...$this->datosDeCaptura($usuario, $estudioData['examenes'], $estudioData['valido'] ?? null),
                ]);

                foreach ($estudioData['examenes'] as $resultado) {
                    $fueraRango = filter_var($resultado['fuera_rango'] ?? false, FILTER_VALIDATE_BOOLEAN);

                    $reporteEstudio->resultados()->create([
                        'examen_id' => $resultado['id'],
                        'resultado' => $resultado['resultado'] ?? '', // la columna no acepta null
                        'fuera_rango' => $fueraRango,
                        'direccion_rango' => $this->resolverDireccionRango(
                            $resultado['id'],
                            $resultado['resultado'] ?? null,
                            $fueraRango,
                            $resultado['direccion_rango'] ?? null,
                            $cliente['sexo'],
                            $cliente['edad'] ?? null,
                        ),
                    ]);
                }
            }

            return $reporte;
        });

        // T12: comision del medico que refiere
        Comisiones::sincronizar($reporte->fresh('estudios'));

        $pendientes = $reporte->estudios()->whereIn('estado', [ReporteEstudio::PENDIENTE, ReporteEstudio::CAPTURADO])->count();

        return response()->json([
            'message' => 'Reporte guardado correctamente',
            'id' => $reporte->id,
            'folio' => $reporte->folio,
            'pendientes_validar' => $usuario->esAdmin() ? 0 : $pendientes,
        ]);
    }

    /**
     * Arma un Reporte completo EN MEMORIA (sin tocar la base de datos) a partir
     * del mismo payload que envia el formulario de captura, para la vista previa.
     */
    private function construirReporteBorrador(array $validated, User $usuario, array $precios, float $descuento): Reporte
    {
        $cliente = $validated['cliente'];

        $reporte = new Reporte([
            'folio' => Reporte::siguienteFolio(),
            'nombre_cliente' => $cliente['nombre'],
            'email' => $cliente['email'] ?? null,
            'fecha_nacimiento' => $cliente['fecha_nacimiento'],
            'edad' => self::edadPaciente($cliente['edad'] ?? null, $cliente['fecha_nacimiento'], $validated['toma_muestra']),
            'sexo' => $cliente['sexo'],
            'toma_muestra' => $validated['toma_muestra'],
            'fecha_reporte' => $validated['fecha_reporte'],
            'fecha_validacion' => $validated['fecha_validacion'] ?? null,
            'medico_solicitante' => $this->nombreMedico($validated['medico_id'] ?? null),
            'medico_id' => $validated['medico_id'] ?? null,
            'aplica_iva' => filter_var($validated['aplica_iva'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'descuento' => $descuento,
        ]);

        $estudios = Estudio::whereIn('id', collect($validated['estudios'])->pluck('id'))->get()->keyBy('id');

        $examenIds = collect($validated['estudios'])
            ->flatMap(fn ($estudio) => collect($estudio['examenes'])->pluck('id'))
            ->unique();
        $examenes = Examen::with('niveles')->whereIn('id', $examenIds)->get()->keyBy('id');

        $reporteEstudios = collect($validated['estudios'])
            ->values()
            ->map(function ($estudioData, $indice) use ($estudios, $examenes, $cliente, $usuario, $precios) {
                $reporteEstudio = new ReporteEstudio([
                    'estudio_id' => $estudioData['id'],
                    'orden' => $estudioData['orden'] ?? $indice,
                    ...collect($this->muestraYMetodo($estudioData, $estudios->get($estudioData['id']), $usuario))
                        ->only(['tipo_muestra', 'metodo'])->all(),
                    'elaboro' => $estudioData['elaboro'] ?? null,
                    'precio' => $precios[$estudioData['id']] ?? 0,
                    'observaciones' => $estudioData['observaciones'] ?? null,
                    'incluir_en_reporte' => filter_var($estudioData['incluir_en_reporte'] ?? true, FILTER_VALIDATE_BOOLEAN),
                    ...collect($this->datosDeCaptura($usuario, $estudioData['examenes'], $estudioData['valido'] ?? null))
                        ->only(['estado', 'valido'])->all(),
                ]);

                $reporteEstudio->setRelation('estudio', $estudios->get($estudioData['id']));

                $resultados = collect($estudioData['examenes'])->map(function ($examenData) use ($examenes, $cliente) {
                    $fueraRango = filter_var($examenData['fuera_rango'] ?? false, FILTER_VALIDATE_BOOLEAN);

                    $resultado = new ResultadoExamen([
                        'examen_id' => $examenData['id'],
                        'resultado' => $examenData['resultado'] ?? '',
                        'fuera_rango' => $fueraRango,
                        'direccion_rango' => $this->resolverDireccionRango(
                            $examenData['id'],
                            $examenData['resultado'] ?? null,
                            $fueraRango,
                            $examenData['direccion_rango'] ?? null,
                            $cliente['sexo'],
                            $cliente['edad'] ?? null,
                        ),
                    ]);

                    $resultado->setRelation('examen', $examenes->get($examenData['id']));

                    return $resultado;
                });

                $reporteEstudio->setRelation('resultados', new EloquentCollection($resultados->all()));

                return $reporteEstudio;
            })
            ->sortBy('orden')
            ->values();

        $reporte->setRelation('estudios', new EloquentCollection($reporteEstudios->all()));

        return $reporte;
    }

    /**
     * Vista previa del reporte (o de la orden de trabajo) ANTES de guardarlo.
     */
    public function previsualizarBorrador(Request $request)
    {
        $usuario = $request->user();
        $validated = $request->validate($this->reglasReporte($usuario->esAdmin()), $this->mensajesReporte());
        [$precios, $descuento] = $this->preciosYDescuento($request, $validated);

        $reporte = $this->construirReporteBorrador($validated, $usuario, $precios, $descuento);

        if ($request->input('documento') === 'orden') {
            $pdf = Pdf::loadView('pdf.orden_trabajo', [
                'reporte' => $reporte,
                'subtotal' => $reporte->subtotal,
                'iva' => $reporte->monto_iva,
                'total' => $reporte->total,
            ])->setPaper('A4', 'portrait');

            ReportePdf::numerarPaginas($pdf, 8);

            return $pdf->stream($reporte->nombreArchivo('Orden de trabajo (vista previa)'));
        }

        $estado = ReportePdf::filtrar($reporte);
        if ($estado['vacio']) {
            return response()->json(['message' => 'Ningún estudio está marcado para incluirse en el reporte.'], 422);
        }

        return ReportePdf::generar($reporte, $estado)->stream($reporte->nombreArchivo('Vista previa'));
    }

    /**
     * Recepcion solo descarga o envia ordenes con todo lo impreso ya validado.
     */
    private function autorizarDescarga(Request $request, array $estado): void
    {
        abort_if($estado['vacio'], 422, 'No hay estudios marcados para incluirse en el reporte.');

        abort_if(
            $estado['preliminar'] && ! $this->esAdmin($request),
            403,
            'Esta orden tiene estudios pendientes de validar. Solo se puede descargar cuando el Q.F.B. la valide.',
        );
    }

    private function marcarEntregados(Reporte $reporte): void
    {
        $ids = $reporte->estudios
            ->filter(fn (ReporteEstudio $e) => $e->estado === ReporteEstudio::VALIDADO)
            ->pluck('id');

        if ($ids->isNotEmpty()) {
            ReporteEstudio::whereIn('id', $ids)->update([
                'estado' => ReporteEstudio::ENTREGADO,
                'entregado_at' => now(),
            ]);
        }
    }

    public function generarPDF(Request $request, $reporteId)
    {
        $reporte = Reporte::with(ReportePdf::RELACIONES)->findOrFail($reporteId);
        $estado = ReportePdf::filtrar($reporte, ReportePdf::idsDesdeRequest($request->query('estudios')));
        $this->autorizarDescarga($request, $estado);

        $pdf = ReportePdf::generar($reporte, $estado);

        // Descargar el PDF definitivo cuenta como entrega al paciente
        if (! $estado['preliminar']) {
            $this->marcarEntregados($reporte);
        }

        return $pdf->download($reporte->nombreArchivo($estado['complementario'] ? 'Complementario' : null));
    }

    public function previsualizarPDF(Request $request, $reporteId)
    {
        $reporte = Reporte::with(ReportePdf::RELACIONES)->findOrFail($reporteId);
        $estado = ReportePdf::filtrar($reporte, ReportePdf::idsDesdeRequest($request->query('estudios')));
        $this->autorizarDescarga($request, $estado);

        return ReportePdf::generar($reporte, $estado)
            ->stream($reporte->nombreArchivo($estado['complementario'] ? 'Complementario' : null));
    }

    /**
     * PDF para el portal de medicos: solo reportes asignados y estudios validados.
     */
    public function pdfMedico($reporteId)
    {
        $reporte = Reporte::with(ReportePdf::RELACIONES)
            ->where('medico_id', session('medico_id'))
            ->findOrFail($reporteId);

        $estado = ReportePdf::filtrar($reporte, null, true);
        abort_if($estado['vacio'], 404, 'Los resultados de esta orden aún no están listos.');

        return ReportePdf::generar($reporte, $estado)->stream($reporte->nombreArchivo());
    }

    /**
     * T6: enlace seguro (el del QR) para mandar el PDF al paciente por WhatsApp o
     * correo. Solo si todo lo que se imprime esta validado; marca la orden entregada.
     */
    public function enlaceEnvio(Request $request, $id)
    {
        $reporte = Reporte::with(['estudios', 'paciente'])->findOrFail($id);
        $estado = ReportePdf::filtrar($reporte);

        if ($estado['vacio']) {
            return response()->json(['message' => 'No hay estudios marcados para incluirse en el reporte.'], 422);
        }

        if ($estado['preliminar']) {
            return response()->json([
                'message' => 'La orden tiene estudios pendientes de validar. Se podrá enviar cuando el Q.F.B. la valide.',
            ], 422);
        }

        if (! $reporte->token_verificacion) {
            $reporte->forceFill(['token_verificacion' => Str::random(40)])->save();
        }

        $this->marcarEntregados($reporte);

        return response()->json([
            'url' => QrVerificacion::url($reporte->token_verificacion),
            'folio' => $reporte->folio,
            'nombre' => $reporte->nombre_cliente,
            'telefono' => $reporte->paciente?->telefono,
            'email' => $reporte->email ?: $reporte->paciente?->email,
        ]);
    }

    /**
     * T2/T6: el Q.F.B. valida los estudios capturados (todos o los indicados).
     */
    public function validar(Request $request, $id)
    {
        $request->validate(['estudios' => 'nullable|array', 'estudios.*' => 'integer']);

        $reporte = Reporte::with('estudios.resultados')->findOrFail($id);
        $ids = $request->input('estudios');
        $usuario = $request->user();
        $validados = 0;

        DB::transaction(function () use ($reporte, $ids, $usuario, &$validados) {
            foreach ($reporte->estudios as $estudio) {
                if ($ids && ! in_array($estudio->id, $ids)) {
                    continue;
                }

                if ($estudio->estado !== ReporteEstudio::CAPTURADO) {
                    continue;
                }

                $estudio->update([
                    'estado' => ReporteEstudio::VALIDADO,
                    'validado_por' => $usuario->id,
                    'validado_at' => now(),
                    'valido' => $estudio->valido ?: config('laboratorio.responsable'),
                ]);
                $validados++;
            }

            if ($validados > 0) {
                $reporte->update(['fecha_validacion' => now()]);
            }
        });

        return response()->json([
            'message' => $validados === 1 ? '1 estudio validado' : "{$validados} estudios validados",
            'validados' => $validados,
        ]);
    }

    /**
     * T2: interruptor "Incluir en reporte" de un estudio de la orden.
     */
    public function cambiarInclusion(Request $request, $id, $estudioId)
    {
        $validated = $request->validate(['incluir_en_reporte' => 'required|boolean']);

        $estudio = ReporteEstudio::where('reporte_id', $id)->findOrFail($estudioId);
        $estudio->update(['incluir_en_reporte' => $validated['incluir_en_reporte']]);

        return response()->json([
            'message' => $estudio->incluir_en_reporte ? 'El estudio se incluirá en el PDF' : 'El estudio no saldrá en el PDF',
            'incluir_en_reporte' => $estudio->incluir_en_reporte,
        ]);
    }

    /**
     * Pagina publica a la que lleva el QR impreso en el reporte.
     */
    public function verificar(string $token)
    {
        $reporte = Reporte::with('estudios.estudio')
            ->where('token_verificacion', $token)
            ->first();

        if ($reporte) {
            ReportePdf::filtrar($reporte, null, true);
        }

        return response()
            ->view('verificacion.reporte', [
                'reporte' => $reporte,
                'token' => $token,
                'paciente' => $reporte ? $this->enmascararNombre($reporte->nombre_cliente) : null,
            ], $reporte ? 200 : 404)
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Cache-Control', 'no-store, private');
    }

    public function descargarVerificado(string $token)
    {
        $reporte = Reporte::with(ReportePdf::RELACIONES)->where('token_verificacion', $token)->firstOrFail();

        $estado = ReportePdf::filtrar($reporte, null, true);
        abort_if($estado['vacio'], 404, 'Los resultados de esta orden aún no están listos.');

        return ReportePdf::generar($reporte, $estado)
            ->stream($reporte->nombreArchivo())
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Cache-Control', 'no-store, private');
    }

    /**
     * "Juan Perez Torres" -> "J*** P**** T*****".
     */
    private function enmascararNombre(?string $nombre): string
    {
        return collect(preg_split('/\s+/u', trim((string) $nombre), -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($parte) => mb_substr($parte, 0, 1) . str_repeat('*', max(mb_strlen($parte) - 1, 2)))
            ->implode(' ');
    }

    public function generarOrdenTrabajo($reporteId)
    {
        $reporte = Reporte::with(['estudios.estudio'])->findOrFail($reporteId);

        $pdf = Pdf::loadView('pdf.orden_trabajo', [
            'reporte' => $reporte,
            'subtotal' => $reporte->subtotal,
            'iva' => $reporte->monto_iva,
            'total' => $reporte->total,
        ])->setPaper('A4', 'portrait');

        ReportePdf::numerarPaginas($pdf, 8);

        return $pdf->download($reporte->nombreArchivo('Orden de trabajo'));
    }

    public function buscarPorFolio($folio)
    {
        $reporte = Reporte::with(['estudios.estudio', 'estudios.resultados.examen'])
            ->where('folio', $folio)
            ->firstOrFail();

        return response()->json($reporte);
    }

    /**
     * Normaliza un resultado para comparar si cambio ("" y null son lo mismo).
     */
    private static function mismoResultado($a, $b): bool
    {
        return trim((string) $a) === trim((string) $b);
    }

    public function actualizarReporte(Request $request, $id)
    {
        $request->validate([
            'descuento' => 'nullable|numeric|min:0|max:100',
            'paciente_id' => 'nullable|integer|exists:pacientes,id',
            'estudios' => 'nullable|array',
            'estudios.*.incluir_en_reporte' => 'nullable|boolean',
            'medico_id' => 'nullable|exists:medicos,id',
        ]);

        $usuario = $request->user();
        $admin = $usuario->esAdmin();
        $reporte = Reporte::with('estudios.resultados')->findOrFail($id);

        DB::transaction(function () use ($request, $reporte, $usuario, $admin) {
            $campos = ['toma_muestra', 'fecha_reporte'];
            if ($admin) {
                $campos[] = 'fecha_validacion';
            }
            $reporte->update($request->only($campos));

            // T12: el medico se elige de la lista. Una orden anterior con el nombre escrito
            // a mano lo conserva mientras no se elija otro.
            if ($request->exists('medico_id')) {
                $medicoId = $request->input('medico_id') ?: null;
                $reporte->update([
                    'medico_id' => $medicoId,
                    'medico_solicitante' => $medicoId
                        ? $this->nombreMedico($medicoId)
                        : ($request->input('medico_solicitante') === $reporte->medico_solicitante ? $reporte->medico_solicitante : null),
                ]);
            }

            if ($request->has('aplica_iva')) {
                $reporte->update(['aplica_iva' => filter_var($request->input('aplica_iva'), FILTER_VALIDATE_BOOLEAN)]);
            }

            // Recepcion no cambia precios ni descuentos
            if ($admin && $request->has('descuento')) {
                $reporte->update(['descuento' => (float) ($request->input('descuento') ?? 0)]);
            }

            $cliente = $request->input('cliente', []);

            // La fecha puede llegar como '2003-09-06' o '2003-09-06T00:00:00.000000Z'
            $fechaNacimiento = ! empty($cliente['fecha_nacimiento'])
                ? substr((string) $cliente['fecha_nacimiento'], 0, 10)
                : $reporte->fecha_nacimiento;

            $reporte->update([
                'nombre_cliente' => $cliente['nombre'] ?? $reporte->nombre_cliente,
                'email' => $cliente['email'] ?? null,
                'fecha_nacimiento' => $fechaNacimiento,
                'edad' => self::edadPaciente($cliente['edad'] ?? null, $fechaNacimiento, $reporte->toma_muestra),
                'sexo' => $cliente['sexo'] ?? null,
            ]);

            // Liga la orden a su paciente (el elegido o el que coincida en nombre + nacimiento)
            if ($request->filled('paciente_id') || ! $reporte->paciente_id) {
                $paciente = $this->resolverPaciente(
                    ['nombre' => $reporte->nombre_cliente, 'fecha_nacimiento' => $fechaNacimiento, 'sexo' => $reporte->sexo, 'email' => $reporte->email],
                    $request->input('paciente_id'),
                );
                $reporte->update(['paciente_id' => $paciente->id]);
            }

            $estudiosPayload = collect($request->input('estudios', []));
            $idsNuevos = $estudiosPayload->pluck('id')->filter();

            // Quitar estudios: Recepcion no puede borrar los ya validados
            $reporte->estudios
                ->reject(fn ($estudio) => $idsNuevos->contains($estudio->id))
                ->each(function (ReporteEstudio $estudio) use ($admin) {
                    if (! $admin && $estudio->estaValidado()) {
                        return;
                    }
                    $estudio->resultados()->delete();
                    $estudio->delete();
                });

            $estudiosCatalogo = Estudio::whereIn('id', $estudiosPayload->pluck('estudio_id')->filter())->get()->keyBy('id');
            $catalogo = $estudiosCatalogo->pluck('precio', 'id');

            foreach ($estudiosPayload as $indice => $estudioData) {
                $reporteEstudio = ! empty($estudioData['id'])
                    ? $reporte->estudios->firstWhere('id', $estudioData['id'])
                    : null;

                $datos = [
                    'orden' => $estudioData['orden'] ?? $indice,
                    ...$this->muestraYMetodo(
                        $estudioData,
                        $estudiosCatalogo->get($estudioData['estudio_id'] ?? $reporteEstudio?->estudio_id),
                        $usuario,
                        $reporteEstudio,
                    ),
                    'elaboro' => $estudioData['elaboro'] ?? '',
                    'observaciones' => $estudioData['observaciones'] ?? null,
                ];
                if (array_key_exists('incluir_en_reporte', $estudioData)) {
                    $datos['incluir_en_reporte'] = filter_var($estudioData['incluir_en_reporte'], FILTER_VALIDATE_BOOLEAN);
                }

                if ($reporteEstudio) {
                    if ($admin) {
                        $datos['precio'] = $estudioData['precio'] ?? 0;
                        $datos['valido'] = $estudioData['valido'] ?? '';
                    }

                    $cambio = $this->guardarResultados($reporteEstudio, $estudioData['resultados'] ?? [], $reporte);

                    // Si cambiaron resultados se vuelve a firmar: validado si fue el Q.F.B.,
                    // pendiente de validar si fue Recepcion.
                    if ($cambio) {
                        $reporteEstudio->load('resultados');
                        $datos = [
                            ...$datos,
                            ...$this->datosDeCaptura($usuario, $reporteEstudio->resultados, $admin ? ($datos['valido'] ?? null) : null),
                        ];
                    }

                    $reporteEstudio->update($datos);
                    continue;
                }

                // Estudio nuevo en la orden
                $estudioId = $estudioData['estudio_id'] ?? $estudioData['id'];
                $resultados = $estudioData['resultados'] ?? [];

                $nuevo = $reporte->estudios()->create([
                    ...$datos,
                    'estudio_id' => $estudioId,
                    'elaboro' => $datos['elaboro'] ?: $usuario->name,
                    'precio' => $admin ? ($estudioData['precio'] ?? 0) : (float) ($catalogo[$estudioId] ?? 0),
                    'incluir_en_reporte' => $datos['incluir_en_reporte'] ?? true,
                    ...$this->datosDeCaptura($usuario, $resultados, $admin ? ($estudioData['valido'] ?? null) : null),
                ]);

                $this->guardarResultados($nuevo, $resultados, $reporte);
            }
        });

        // T12: si cambiaron estudios, descuento, IVA o medico, la comision se recalcula
        Comisiones::sincronizar($reporte->fresh('estudios'));

        return response()->json(['message' => 'Reporte actualizado correctamente']);
    }

    /**
     * Edad en años a la toma de muestra. Usa la que mandó la pantalla y, si no
     * llegó, la calcula con la fecha de nacimiento (el PDF nunca dice "Edad: años").
     */
    private static function edadPaciente($edad, $fechaNacimiento, $tomaMuestra): ?int
    {
        if ($edad !== null && $edad !== '') {
            return (int) $edad;
        }
        if (! $fechaNacimiento) {
            return null;
        }

        $referencia = $tomaMuestra ? \Carbon\Carbon::parse($tomaMuestra) : now();

        return (int) \Carbon\Carbon::parse($fechaNacimiento)->diffInYears($referencia);
    }

    /**
     * Crea o actualiza los resultados de un estudio. Devuelve true si algun valor cambio.
     */
    private function guardarResultados(ReporteEstudio $reporteEstudio, array $resultados, Reporte $reporte): bool
    {
        $cambio = false;
        $existentes = $reporteEstudio->relationLoaded('resultados') ? $reporteEstudio->resultados : $reporteEstudio->resultados()->get();

        foreach ($resultados as $resultadoData) {
            $fueraRango = filter_var($resultadoData['fuera_rango'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $valor = $resultadoData['resultado'] ?? '';

            $resultado = ! empty($resultadoData['id']) ? $existentes->firstWhere('id', $resultadoData['id']) : null;
            $examenId = $resultado?->examen_id ?? ($resultadoData['examen_id'] ?? $resultadoData['id'] ?? null);

            if (! $examenId) {
                continue;
            }

            $datos = [
                'resultado' => $valor ?? '', // la columna no acepta null
                'fuera_rango' => $fueraRango,
                'direccion_rango' => $this->resolverDireccionRango(
                    $examenId, $valor, $fueraRango, $resultadoData['direccion_rango'] ?? null, $reporte->sexo, $reporte->edad,
                ),
            ];

            if ($resultado) {
                if (! self::mismoResultado($resultado->resultado, $valor)) {
                    $cambio = true;
                }
                $resultado->update($datos);
            } else {
                $reporteEstudio->resultados()->create(['examen_id' => $examenId, ...$datos]);
                if (trim((string) $valor) !== '') {
                    $cambio = true;
                }
            }
        }

        return $cambio;
    }

    /**
     * Estado de la orden a partir de sus estudios incluidos en el PDF.
     */
    public static function estadoOrden($estudios): string
    {
        $incluidos = collect($estudios)->filter(fn ($e) => $e->incluir_en_reporte !== false);
        $estados = $incluidos->pluck('estado');

        return match (true) {
            $incluidos->isEmpty() => 'sin_estudios',
            $estados->contains(ReporteEstudio::CAPTURADO) => 'por_validar',
            $estados->contains(ReporteEstudio::PENDIENTE) => 'en_proceso',
            $estados->every(fn ($e) => $e === ReporteEstudio::ENTREGADO) => 'entregado',
            default => 'listo',
        };
    }

    public function index(Request $request)
    {
        $query = Reporte::with(['estudios.estudio'])
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('folio', 'like', "%{$search}%")
                    ->orWhere('nombre_cliente', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->fecha_hasta);
        }

        // Atajo para el Q.F.B.: ordenes con estudios capturados sin validar
        if ($request->input('estado') === 'por_validar') {
            $query->whereHas('estudios', fn ($q) => $q->where('estado', ReporteEstudio::CAPTURADO)->where('incluir_en_reporte', true));
        }

        $reportes = $query->paginate(15)->onEachSide(1)->withQueryString()->through(function ($reporte) {
            return [
                'id' => $reporte->id,
                'folio' => $reporte->folio,
                'paciente_id' => $reporte->paciente_id,
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
                'aplica_iva' => (bool) $reporte->aplica_iva,
                'total_estudios' => $reporte->estudios->count(),
                'subtotal' => $reporte->subtotal,
                'descuento' => (float) $reporte->descuento,
                'monto_descuento' => $reporte->monto_descuento,
                'iva' => $reporte->monto_iva,
                'total_precio' => $reporte->total,
                'estado' => self::estadoOrden($reporte->estudios),
            ];
        });

        $porValidar = Reporte::whereHas('estudios', fn ($q) => $q->where('estado', ReporteEstudio::CAPTURADO)->where('incluir_en_reporte', true))->count();

        return Inertia::render('Reportes/Index', [
            'reportes' => $reportes,
            'filters' => $request->only(['search', 'fecha_desde', 'fecha_hasta', 'estado']),
            'porValidar' => $porValidar,
        ]);
    }

    /**
     * Detalle editable de una orden.
     */
    public function show($id)
    {
        $reporte = Reporte::with([
            'paciente',
            'estudios.estudio',
            'estudios.resultados.examen.niveles',
            'estudios.capturadoPor:id,name',
            'estudios.validadoPor:id,name',
            'estudios.muestraMetodoCambiadoPor:id,name',
        ])->findOrFail($id);

        return Inertia::render('Reportes/Show', [
            'reporte' => [
                'id' => $reporte->id,
                'folio' => $reporte->folio,
                'paciente_id' => $reporte->paciente_id,
                'paciente_telefono' => $reporte->paciente?->telefono,
                'nombre_cliente' => $reporte->nombre_cliente,
                'email' => $reporte->email,
                'fecha_nacimiento' => $reporte->fecha_nacimiento,
                'edad' => $reporte->edad,
                'sexo' => $reporte->sexo,
                'toma_muestra' => $reporte->toma_muestra,
                'fecha_reporte' => $reporte->fecha_reporte,
                'fecha_validacion' => $reporte->fecha_validacion,
                'medico_solicitante' => $reporte->medico_solicitante,
                'medico_id' => $reporte->medico_id,
                'aplica_iva' => (bool) $reporte->aplica_iva,
                'descuento' => (float) $reporte->descuento,
                'porcentaje_iva' => (float) config('facturacion.iva'),
                'created_at' => $reporte->created_at,
                'estado' => self::estadoOrden($reporte->estudios),
                'estudios' => $reporte->estudios->map(fn (ReporteEstudio $re) => [
                    'id' => $re->id,
                    'estudio_id' => $re->estudio_id,
                    'orden' => $re->orden,
                    'nombre' => $re->estudio->nombre,
                    'precio' => $re->precio,
                    'elaboro' => $re->elaboro,
                    'valido' => $re->valido,
                    'tipo_muestra' => $re->tipo_muestra,
                    'metodo' => $re->metodo,
                    // T13: lo de siempre y las otras opciones del catalogo, y quien lo cambio
                    'muestra_catalogo' => $re->estudio->tipo_muestra,
                    'metodo_catalogo' => $re->estudio->metodo,
                    'opciones_muestra' => $re->estudio->opciones_muestra ?? [],
                    'opciones_metodo' => $re->estudio->opciones_metodo ?? [],
                    'muestra_metodo_cambiado_por' => $re->muestraMetodoCambiadoPor?->name,
                    'muestra_metodo_cambiado_at' => $re->muestra_metodo_cambiado_at,
                    'leyenda' => $re->estudio->leyenda,
                    'observaciones' => $re->observaciones,
                    'incluir_en_reporte' => $re->incluir_en_reporte !== false,
                    'estado' => $re->estado,
                    'capturado_por' => $re->capturadoPor?->name,
                    'capturado_at' => $re->capturado_at,
                    'validado_por' => $re->validadoPor?->name,
                    'validado_at' => $re->validado_at,
                    'entregado_at' => $re->entregado_at,
                    'resultados' => $re->resultados->map(fn ($resultado) => [
                        'id' => $resultado->id,
                        'examen_id' => $resultado->examen_id,
                        'nombre_examen' => $resultado->examen->nombre_examen,
                        'resultado' => $resultado->resultado,
                        'unidad' => $resultado->examen->unidad,
                        'valor_referencia' => $resultado->examen->valor_referencia,
                        'niveles' => $resultado->examen->niveles,
                        'fuera_rango' => $resultado->fuera_rango,
                        'direccion_rango' => $resultado->direccion_rango,
                    ]),
                ]),
            ],
        ]);
    }
}
