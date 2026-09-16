<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Metricas de negocio del dashboard.
 *
 * Convenciones:
 * - Venta neta = precio del estudio menos el descuento del reporte (sin IVA).
 * - La fecha de una orden es su toma de muestra (capturada en hora local).
 *   created_at no sirve: los reportes se reutilizan desde Reimpresion y
 *   conservan la fecha de creacion original. Solo si falta la toma se usa
 *   created_at (UTC) convertido a la zona del laboratorio.
 * - Un "paciente" se identifica por el nombre del reporte normalizado, porque
 *   los reportes no tienen llave hacia la tabla pacientes.
 */
class DashboardMetrics
{
    /** Valores de medico_solicitante que no son un medico real. */
    private const MEDICOS_EXCLUIDOS = ['', 'NA', 'N A', 'NINGUNO', 'NO APLICA', 'PARTICULAR', 'SIN MEDICO', 'A QUIEN CORRESPONDA', 'X'];

    /** Tiempos de entrega fuera de este rango (horas) se consideran captura erronea. */
    private const ENTREGA_MAX_HORAS = 720;

    /** Ordenes minimas en el periodo anterior para mostrar variaciones. */
    private const MIN_ORDENES_COMPARATIVO = 5;

    private const NETO = 're.precio * (1 - COALESCE(r.descuento, 0) / 100)';
    private const PACIENTE = 'LOWER(TRIM(r.nombre_cliente))';

    private string $tz;
    private string $fecha;

    public function __construct(
        private CarbonInterface $inicio,
        private CarbonInterface $fin,
    ) {
        $this->tz = config('app.local_timezone', 'America/Cancun');
        $this->fecha = "COALESCE(r.toma_muestra, (r.created_at AT TIME ZONE 'UTC') AT TIME ZONE '{$this->tz}')";
    }

    public function todo(): array
    {
        $dias = (int) $this->inicio->diffInDays($this->fin) + 1;
        $prevFin = $this->inicio->copy()->subDay()->endOfDay();
        $prevInicio = $prevFin->copy()->subDays($dias - 1)->startOfDay();

        $actual = $this->resumen($this->inicio, $this->fin);
        $anterior = $this->resumen($prevInicio, $prevFin);
        $granularidad = $dias <= 45 ? 'day' : ($dias <= 190 ? 'week' : 'month');

        $serie = $this->serie($this->inicio, $this->fin, $granularidad);
        $seriePrev = $this->serie($prevInicio, $prevFin, $granularidad);

        $estudios = $this->estudiosTop();
        $heatmap = $this->heatmap();
        $medicos = $this->medicos();
        $cotizaciones = $this->cotizaciones();

        $data = [
            'periodo' => [
                'inicio' => $this->inicio->toDateString(),
                'fin' => $this->fin->toDateString(),
                'dias' => $dias,
                'prev_inicio' => $prevInicio->toDateString(),
                'prev_fin' => $prevFin->toDateString(),
                'granularidad' => $granularidad,
            ],
            'kpis' => $this->kpis($actual, $anterior),
            'serie' => $this->alinearSeries($serie, $seriePrev),
            'estudios' => $estudios,
            'heatmap' => $heatmap,
            'medicos' => $medicos,
            'demografia' => $this->demografia(),
            'cotizaciones' => $cotizaciones,
            'hoy' => $this->hoy(),
        ];

        $data['insights'] = $this->insights($data, $actual, $anterior);

        return $data;
    }

    /** Filtra por fecha de la orden; $a y $b ya vienen en hora local. */
    private function rango($query, CarbonInterface $a, CarbonInterface $b, ?string $col = null)
    {
        $limites = [$a->toDateTimeString(), $b->toDateTimeString()];

        return $col
            ? $query->whereBetween($col, $limites)
            : $query->whereRaw("{$this->fecha} BETWEEN ? AND ?", $limites);
    }

    private function base(CarbonInterface $a, CarbonInterface $b)
    {
        return $this->rango(
            DB::table('reportes as r')
                ->leftJoin('reporte_estudio as re', 're.reporte_id', '=', 'r.id')
                ->leftJoin('estudios as e', 'e.id', '=', 're.estudio_id'),
            $a,
            $b
        );
    }

    private function resumen(CarbonInterface $a, CarbonInterface $b): array
    {
        $neto = self::NETO;
        $row = $this->base($a, $b)->selectRaw("
            COUNT(DISTINCT r.id) AS ordenes,
            COUNT(DISTINCT CASE WHEN re.precio > 0 THEN r.id END) AS cobradas,
            COUNT(re.id) AS estudios,
            COUNT(DISTINCT " . self::PACIENTE . ") AS pacientes,
            COALESCE(SUM(re.precio), 0) AS brutas,
            COALESCE(SUM(re.precio * COALESCE(r.descuento, 0) / 100), 0) AS descuentos,
            COALESCE(SUM($neto), 0) AS netas,
            COALESCE(SUM(CASE WHEN e.costo > 0 AND re.precio > 0 THEN $neto END), 0) AS netas_con_costo,
            COALESCE(SUM(CASE WHEN e.costo > 0 AND re.precio > 0 THEN e.costo END), 0) AS costo,
            COALESCE(SUM(CASE WHEN COALESCE(re.precio, 0) = 0 THEN e.precio END), 0) AS no_cobrado
        ")->first();

        // Ordenes con estudios pero sin ningun precio capturado
        $sinPrecio = DB::query()->fromSub(
            $this->base($a, $b)->whereNotNull('re.id')
                ->groupBy('r.id')
                ->havingRaw('SUM(COALESCE(re.precio, 0)) = 0')
                ->select('r.id'),
            'x'
        )->count();

        $entrega = $this->rango(DB::table('reportes as r'), $a, $b)
            ->whereNotNull('r.toma_muestra')
            ->whereNotNull('r.fecha_validacion')
            ->selectRaw('EXTRACT(EPOCH FROM (r.fecha_validacion - r.toma_muestra)) / 3600 AS h');
        $entrega = DB::query()->fromSub($entrega, 't')
            ->whereBetween('h', [0, self::ENTREGA_MAX_HORAS])
            ->selectRaw('PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY h) AS mediana,
                         AVG(CASE WHEN h <= 24 THEN 1.0 ELSE 0 END) AS pct_24h,
                         COUNT(*) AS n')
            ->first();

        $fuera = $this->rango(
            DB::table('resultado_examen as rx')
                ->join('reporte_estudio as re', 're.id', '=', 'rx.reporte_estudio_id')
                ->join('reportes as r', 'r.id', '=', 're.reporte_id'),
            $a,
            $b
        )->selectRaw('AVG(CASE WHEN rx.fuera_rango THEN 1.0 ELSE 0 END) AS pct, COUNT(*) AS n')->first();

        $pendientes = $this->rango(DB::table('reportes as r'), $a, $b)
            ->whereNull('r.fecha_validacion')->count();

        $pacientes = $this->pacientesNuevos($a, $b);

        $ordenes = (int) $row->ordenes;
        $netas = (float) $row->netas;

        return [
            'ordenes' => $ordenes,
            'estudios' => (int) $row->estudios,
            'pacientes' => (int) $row->pacientes,
            'brutas' => (float) $row->brutas,
            'descuentos' => (float) $row->descuentos,
            'netas' => $netas,
            // Ordenes sin precio (capturas previas a los precios) no diluyen el ticket
            'ticket' => $row->cobradas > 0 ? $netas / $row->cobradas : 0,
            'estudios_por_orden' => $ordenes > 0 ? $row->estudios / $ordenes : 0,
            'utilidad' => (float) $row->netas_con_costo - (float) $row->costo,
            'margen' => $row->netas_con_costo > 0
                ? ((float) $row->netas_con_costo - (float) $row->costo) / (float) $row->netas_con_costo
                : null,
            'cobertura_costo' => $netas > 0 ? (float) $row->netas_con_costo / $netas : 0,
            'entrega_mediana_h' => $entrega->mediana !== null ? (float) $entrega->mediana : null,
            'entrega_24h' => $entrega->n > 0 ? (float) $entrega->pct_24h : null,
            'fuera_rango' => $fuera->n > 0 ? (float) $fuera->pct : null,
            'pendientes_validar' => $pendientes,
            'ordenes_sin_precio' => $sinPrecio,
            'no_cobrado' => (float) $row->no_cobrado,
            'pacientes_nuevos' => $pacientes['nuevos'],
            'pacientes_recurrentes' => $pacientes['recurrentes'],
            'recurrencia' => $pacientes['total'] > 0 ? $pacientes['recurrentes'] / $pacientes['total'] : null,
        ];
    }

    /**
     * Nuevos: sin ordenes antes del periodo. Recurrentes: mas de una orden
     * en su historial hasta el fin del periodo.
     */
    private function pacientesNuevos(CarbonInterface $a, CarbonInterface $b): array
    {
        $desde = $a->toDateTimeString();
        $hasta = $b->toDateTimeString();
        $p = self::PACIENTE;
        $f = $this->fecha;

        $row = DB::selectOne("
            WITH p AS (
                SELECT DISTINCT $p AS n FROM reportes r
                WHERE $f BETWEEN ? AND ?
            ), h AS (
                SELECT p.n,
                       COUNT(*) FILTER (WHERE $f < ?) AS antes,
                       COUNT(*) AS hasta
                FROM p JOIN reportes r ON $p = p.n AND $f <= ?
                GROUP BY p.n
            )
            SELECT COUNT(*) AS total,
                   COUNT(*) FILTER (WHERE antes = 0) AS nuevos,
                   COUNT(*) FILTER (WHERE hasta > 1) AS recurrentes
            FROM h
        ", [$desde, $hasta, $desde, $hasta]);

        return [
            'total' => (int) $row->total,
            'nuevos' => (int) $row->nuevos,
            'recurrentes' => (int) $row->recurrentes,
        ];
    }

    private function kpis(array $act, array $prev): array
    {
        // Con muy pocas ordenes previas las variaciones (+20,000%) son ruido
        $comparable = $prev['ordenes'] >= self::MIN_ORDENES_COMPARATIVO;
        $kpi = fn (string $k) => [
            'valor' => $act[$k],
            'anterior' => $prev[$k],
            'delta' => $comparable ? $this->delta($act[$k], $prev[$k]) : null,
        ];

        return [
            'ventas' => $kpi('netas'),
            'ordenes' => $kpi('ordenes'),
            'ticket' => $kpi('ticket'),
            'pacientes' => $kpi('pacientes'),
            'estudios' => $kpi('estudios'),
            'estudios_por_orden' => $kpi('estudios_por_orden'),
            'descuentos' => $kpi('descuentos'),
            'utilidad' => $kpi('utilidad'),
            'entrega' => $kpi('entrega_mediana_h'),
            'fuera_rango' => $kpi('fuera_rango'),
            'recurrencia' => $kpi('recurrencia'),
            'comparable' => $comparable,
            'extra' => [
                'brutas' => $act['brutas'],
                'margen' => $act['margen'],
                'cobertura_costo' => $act['cobertura_costo'],
                'entrega_24h' => $act['entrega_24h'],
                'pendientes_validar' => $act['pendientes_validar'],
                'ordenes_sin_precio' => $act['ordenes_sin_precio'],
                'no_cobrado' => $act['no_cobrado'],
                'pacientes_nuevos' => $act['pacientes_nuevos'],
                'pacientes_recurrentes' => $act['pacientes_recurrentes'],
            ],
        ];
    }

    private function delta($actual, $anterior): ?float
    {
        if ($actual === null || $anterior === null || (float) $anterior <= 0.0) {
            return null;
        }

        return ((float) $actual - (float) $anterior) / abs((float) $anterior);
    }

    private function serie(CarbonInterface $a, CarbonInterface $b, string $gran): array
    {
        $neto = self::NETO;
        $filas = $this->base($a, $b)
            ->selectRaw("TO_CHAR(DATE_TRUNC('$gran', {$this->fecha}), 'YYYY-MM-DD') AS k,
                         COUNT(DISTINCT r.id) AS ordenes,
                         COUNT(DISTINCT CASE WHEN re.precio > 0 THEN r.id END) AS cobradas,
                         COALESCE(SUM($neto), 0) AS ventas")
            ->groupByRaw('1')
            ->get()
            ->keyBy('k');

        $puntos = [];
        $cursor = match ($gran) {
            'day' => $a->copy()->startOfDay(),
            'week' => $a->copy()->startOfWeek(Carbon::MONDAY),
            'month' => $a->copy()->startOfMonth(),
        };

        while ($cursor->lte($b)) {
            $k = $cursor->toDateString();
            $f = $filas->get($k);
            $ordenes = $f ? (int) $f->ordenes : 0;
            $ventas = $f ? (float) $f->ventas : 0.0;
            $cobradas = $f ? (int) $f->cobradas : 0;
            $loc = $cursor->copy()->locale('es');

            $puntos[] = [
                'k' => $k,
                'label' => match ($gran) {
                    'day' => $loc->translatedFormat('j M'),
                    'week' => 'Sem ' . $loc->translatedFormat('j M'),
                    'month' => $loc->translatedFormat('M Y'),
                },
                'ventas' => round($ventas, 2),
                'ordenes' => $ordenes,
                'ticket' => $cobradas > 0 ? round($ventas / $cobradas, 2) : null,
            ];

            $cursor = match ($gran) {
                'day' => $cursor->addDay(),
                'week' => $cursor->addWeek(),
                'month' => $cursor->addMonth(),
            };
        }

        return $puntos;
    }

    /** Empata el periodo anterior por posicion para dibujarlo encima. */
    private function alinearSeries(array $actual, array $prev): array
    {
        foreach ($actual as $i => &$p) {
            $q = $prev[$i] ?? null;
            $p['prev_label'] = $q['label'] ?? null;
            $p['prev_ventas'] = $q['ventas'] ?? null;
            $p['prev_ordenes'] = $q['ordenes'] ?? null;
            $p['prev_ticket'] = $q['ticket'] ?? null;
        }

        return $actual;
    }

    private function estudiosTop(): array
    {
        $neto = self::NETO;
        $filas = $this->base($this->inicio, $this->fin)
            ->whereNotNull('re.id')
            ->selectRaw("e.id, COALESCE(e.nombre, 'Estudio eliminado') AS nombre,
                         COUNT(*) AS cantidad,
                         COALESCE(SUM($neto), 0) AS ventas,
                         MAX(e.costo) AS costo")
            ->groupBy('e.id', 'e.nombre')
            ->get()
            ->each(function ($f) {
                $f->ventas = (float) $f->ventas;
                $f->cantidad = (int) $f->cantidad;
            });

        $total = (float) $filas->sum('ventas');
        $totalCant = (int) $filas->sum('cantidad');

        $mapear = fn ($f) => [
            'id' => $f->id,
            'nombre' => $f->nombre,
            'cantidad' => (int) $f->cantidad,
            'ventas' => round((float) $f->ventas, 2),
            'participacion' => $total > 0 ? (float) $f->ventas / $total : 0,
            'precio_medio' => $f->cantidad > 0 ? round((float) $f->ventas / $f->cantidad, 2) : 0,
            'tiene_costo' => (float) $f->costo > 0,
        ];

        // Cuantos estudios generan el 80% de las ventas (regla de Pareto)
        $acum = 0;
        $pareto = 0;
        foreach ($filas->sortByDesc('ventas') as $f) {
            $acum += (float) $f->ventas;
            $pareto++;
            if ($total > 0 && $acum / $total >= 0.8) {
                break;
            }
        }

        return [
            'por_ventas' => $filas->sortByDesc('ventas')->take(10)->map($mapear)->values(),
            'por_cantidad' => $filas->sortByDesc('cantidad')->take(10)->map($mapear)->values(),
            'total_ventas' => $total,
            'total_cantidad' => $totalCant,
            'distintos' => $filas->count(),
            'pareto_80' => $total > 0 ? $pareto : 0,
        ];
    }

    private function heatmap(): array
    {
        $filas = $this->rango(DB::table('reportes as r'), $this->inicio, $this->fin)
            ->selectRaw("EXTRACT(ISODOW FROM {$this->fecha})::int AS d,
                         EXTRACT(HOUR FROM {$this->fecha})::int AS h,
                         COUNT(*) AS n")
            ->groupByRaw('1, 2')
            ->get();

        // matriz[dia 0=lunes][hora]
        $matriz = array_fill(0, 7, array_fill(0, 24, 0));
        foreach ($filas as $f) {
            $matriz[$f->d - 1][$f->h] = (int) $f->n;
        }

        return ['matriz' => $matriz];
    }

    /** Nombre normalizado en SQL: sin "Dr./Dra.", acentos, puntuacion ni espacios dobles. */
    private function normalizarSql(string $col): string
    {
        return "TRIM(REGEXP_REPLACE(REGEXP_REPLACE(
                    TRANSLATE(UPPER(TRIM(COALESCE($col, ''))), 'ÁÉÍÓÚÜÑ', 'AEIOUUN'),
                    '^DRA?(\\.|\\s)\\s*', ''), '[^A-Z0-9]+', ' ', 'g'))";
    }

    private function medicos(): array
    {
        $neto = self::NETO;
        $nombre = "CASE WHEN m.id IS NOT NULL THEN {$this->normalizarSql('m.nombre')}
                        ELSE {$this->normalizarSql('r.medico_solicitante')} END";

        $filas = $this->base($this->inicio, $this->fin)
            ->leftJoin('medicos as m', 'm.id', '=', 'r.medico_id')
            ->selectRaw("m.id AS medico_id, $nombre AS nombre,
                         COUNT(DISTINCT r.id) AS ordenes,
                         COALESCE(SUM($neto), 0) AS ventas")
            ->groupByRaw('1, 2')
            ->get();

        // Medicos del portal: el texto libre "LUIS YAMA" se asigna a "LUIS YAMA CHAC"
        $registrados = DB::table('medicos as m')
            ->selectRaw("m.id, m.nombre, {$this->normalizarSql('m.nombre')} AS clave")
            ->get();

        $grupos = [];
        $particulares = 0;
        foreach ($filas as $f) {
            $texto = (string) $f->nombre;
            $id = $f->medico_id;

            if (!$id && in_array($texto, self::MEDICOS_EXCLUIDOS, true)) {
                $particulares += (int) $f->ordenes;
                continue;
            }

            if (!$id && strlen($texto) >= 8) {
                $id = $registrados->first(fn ($m) => $m->clave === $texto
                    || str_starts_with($m->clave, $texto . ' ')
                    || str_starts_with($texto, $m->clave . ' '))?->id;
            }

            $clave = $id ? "m$id" : "t$texto";
            $grupos[$clave] ??= [
                'nombre' => $id ? $registrados->firstWhere('id', $id)->nombre : $texto,
                'registrado' => (bool) $id,
                'ordenes' => 0,
                'ventas' => 0.0,
            ];
            $grupos[$clave]['ordenes'] += (int) $f->ordenes;
            $grupos[$clave]['ventas'] += (float) $f->ventas;
        }

        $grupos = $this->unirParecidos($grupos);

        $referidos = collect($grupos);
        $totalReferidas = (int) $referidos->sum('ordenes');
        $total = $totalReferidas + $particulares;

        return [
            'top' => $referidos->sortByDesc('ventas')->take(8)->map(fn ($g) => [
                ...$g,
                'ventas' => round($g['ventas'], 2),
            ])->values(),
            'total_medicos' => $referidos->count(),
            'ordenes_referidas' => $totalReferidas,
            'ordenes_particulares' => $particulares,
            'pct_referidas' => $total > 0 ? $totalReferidas / $total : 0,
            'ventas_referidas' => round((float) $referidos->sum('ventas'), 2),
        ];
    }

    /** Une nombres escritos a mano que difieren por un par de letras. */
    private function unirParecidos(array $grupos): array
    {
        uasort($grupos, fn ($a, $b) => $b['ordenes'] <=> $a['ordenes']);
        $claves = array_keys($grupos);

        foreach ($claves as $i => $base) {
            if (!isset($grupos[$base]) || strlen($grupos[$base]['nombre']) < 10) {
                continue;
            }
            foreach (array_slice($claves, $i + 1) as $otra) {
                if (!isset($grupos[$otra]) || $grupos[$otra]['registrado']) {
                    continue;
                }
                if (levenshtein($grupos[$base]['nombre'], $grupos[$otra]['nombre']) <= 2) {
                    $grupos[$base]['ordenes'] += $grupos[$otra]['ordenes'];
                    $grupos[$base]['ventas'] += $grupos[$otra]['ventas'];
                    unset($grupos[$otra]);
                }
            }
        }

        return $grupos;
    }

    private function demografia(): array
    {
        $edad = "COALESCE(r.edad, EXTRACT(YEAR FROM AGE({$this->fecha}, r.fecha_nacimiento))::int)";
        $grupos = ['0-11', '12-17', '18-29', '30-44', '45-59', '60+', 'Sin dato'];

        $filas = $this->rango(DB::table('reportes as r'), $this->inicio, $this->fin)
            ->selectRaw("CASE
                    WHEN $edad IS NULL OR $edad < 0 THEN 'Sin dato'
                    WHEN $edad < 12 THEN '0-11'
                    WHEN $edad < 18 THEN '12-17'
                    WHEN $edad < 30 THEN '18-29'
                    WHEN $edad < 45 THEN '30-44'
                    WHEN $edad < 60 THEN '45-59'
                    ELSE '60+' END AS grupo,
                CASE WHEN r.sexo IN ('Femenino', 'Masculino') THEN r.sexo ELSE 'Sin dato' END AS sexo,
                COUNT(*) AS n")
            ->groupByRaw('1, 2')
            ->get();

        $series = [];
        foreach (['Femenino', 'Masculino', 'Sin dato'] as $sexo) {
            $series[$sexo] = array_map(
                fn ($g) => (int) ($filas->first(fn ($f) => $f->grupo === $g && $f->sexo === $sexo)->n ?? 0),
                $grupos
            );
        }

        // Sin la columna "Sin dato" cuando todas las ordenes traen edad
        $ultimo = count($grupos) - 1;
        if (array_sum(array_column($series, $ultimo)) === 0) {
            array_pop($grupos);
            $series = array_map(fn ($s) => array_slice($s, 0, $ultimo), $series);
        }

        return ['grupos' => $grupos, 'series' => $series];
    }

    /**
     * Las cotizaciones casi nunca cambian de estado, asi que la conversion se
     * estima buscando una orden del mismo paciente (nombre de al menos dos
     * palabras contenido en el de la orden) en los 45 dias siguientes.
     */
    private function cotizaciones(): array
    {
        $sinAcento = fn ($col) => "LOWER(TRANSLATE(TRIM($col), 'ÁÉÍÓÚÜÑáéíóúüñ', 'AEIOUUNaeiouun'))";
        $nc = $sinAcento('c.nombre_cliente');
        $identificada = "($nc NOT IN ('cotizacion', 'cotizaciones', 'paciente', 'cliente') AND POSITION(' ' IN TRIM(c.nombre_cliente)) > 0)";

        $orden = DB::table('reportes as r')
            ->select('r.id')
            ->whereRaw("{$this->fecha} >= c.fecha_cotizacion")
            ->whereRaw("{$this->fecha} < c.fecha_cotizacion + INTERVAL '45 day'")
            ->whereRaw("{$sinAcento('r.nombre_cliente')} LIKE '%' || $nc || '%'")
            ->whereRaw($identificada)
            ->limit(1);

        $filas = $this->rango(DB::table('cotizaciones as c'), $this->inicio, $this->fin, 'c.fecha_cotizacion')
            ->leftJoinLateral($orden, 'o')
            ->selectRaw("c.id, c.total, c.estado, $identificada AS identificada, o.id AS orden_id")
            ->get();

        $total = $filas->count();
        $identificadas = $filas->filter(fn ($f) => (bool) $f->identificada);
        $convertidas = $filas->whereNotNull('orden_id');
        $monto = (float) $filas->sum('total');

        $topCotizados = $this->rango(
            DB::table('cotizacion_estudios as ce')
                ->join('cotizaciones as c', 'c.id', '=', 'ce.cotizacion_id')
                ->leftJoin('estudios as e', 'e.id', '=', 'ce.estudio_id'),
            $this->inicio,
            $this->fin,
            'c.fecha_cotizacion'
        )
            ->selectRaw("COALESCE(e.nombre, ce.descripcion, 'Otro') AS nombre, SUM(COALESCE(ce.cantidad, 1)) AS n")
            ->groupByRaw('1')
            ->orderByDesc('n')
            ->limit(5)
            ->get()
            ->map(fn ($f) => ['nombre' => $f->nombre, 'n' => (int) $f->n]);

        return [
            'total' => $total,
            'monto_total' => $monto,
            'ticket' => $total > 0 ? $monto / $total : 0,
            'etapas' => [
                ['clave' => 'emitidas', 'label' => 'Emitidas', 'n' => $total, 'monto' => $monto],
                ['clave' => 'identificadas', 'label' => 'Con nombre', 'n' => $identificadas->count(),
                    'monto' => (float) $identificadas->sum('total')],
                ['clave' => 'convertidas', 'label' => 'Se hicieron', 'n' => $convertidas->count(),
                    'monto' => (float) $convertidas->sum('total')],
            ],
            'anonimas' => $total - $identificadas->count(),
            'conversion' => $identificadas->count() > 0 ? $convertidas->count() / $identificadas->count() : null,
            'sin_seguimiento' => $filas->where('estado', 'pendiente_pago')->count(),
            'top_estudios' => $topCotizados,
        ];
    }

    /** Pulso del dia y cierre de mes: no depende del filtro. */
    private function hoy(): array
    {
        $ahora = Carbon::now($this->tz);
        $hoy = $this->resumenRapido($ahora->copy()->startOfDay(), $ahora->copy()->endOfDay());
        $mes = $this->resumenRapido($ahora->copy()->startOfMonth(), $ahora->copy()->endOfDay());
        $mesAnt = $this->resumenRapido(
            $ahora->copy()->subMonthNoOverflow()->startOfMonth(),
            $ahora->copy()->subMonthNoOverflow()->endOfMonth()
        );
        // Mismo avance del mes anterior, para comparar peras con peras
        $mesAntAlDia = $this->resumenRapido(
            $ahora->copy()->subMonthNoOverflow()->startOfMonth(),
            $ahora->copy()->subMonthNoOverflow()->endOfDay()
        );

        $diaDelMes = $ahora->day;
        $diasMes = $ahora->daysInMonth;

        $pendientes = $this->rango(DB::table('reportes as r'), $ahora->copy()->subDays(30)->startOfDay(), $ahora)
            ->whereNull('r.fecha_validacion')
            ->count();

        return [
            'fecha' => $ahora->copy()->locale('es')->translatedFormat('l j \d\e F'),
            'mes_nombre' => $ahora->copy()->locale('es')->translatedFormat('F'),
            'ordenes_hoy' => $hoy['ordenes'],
            'ventas_hoy' => $hoy['ventas'],
            'ventas_mes' => $mes['ventas'],
            'ordenes_mes' => $mes['ordenes'],
            'proyeccion_mes' => $mes['ventas'] / $diaDelMes * $diasMes,
            'avance_mes' => $diaDelMes / $diasMes,
            'mes_anterior' => $mesAnt['ventas'],
            'delta_mismo_avance' => $this->delta($mes['ventas'], $mesAntAlDia['ventas']),
            'pendientes_validar' => $pendientes,
        ];
    }

    private function resumenRapido(CarbonInterface $a, CarbonInterface $b): array
    {
        $row = $this->base($a, $b)
            ->selectRaw('COUNT(DISTINCT r.id) AS ordenes, COALESCE(SUM(' . self::NETO . '), 0) AS ventas')
            ->first();

        return ['ordenes' => (int) $row->ordenes, 'ventas' => (float) $row->ventas];
    }

    /**
     * Hallazgos en lenguaje natural, ordenados por relevancia.
     * tono: bueno | alerta | info
     */
    private function insights(array $d, array $act, array $prev): array
    {
        $out = [];
        $money = fn ($v) => '$' . number_format($v, 0);
        $pct = fn ($v) => number_format($v * 100, 0) . '%';

        if ($act['ordenes'] === 0) {
            return [[
                'tono' => 'info',
                'titulo' => 'Sin órdenes en el periodo',
                'texto' => 'No hay reportes registrados en estas fechas. Prueba con un rango más amplio.',
            ]];
        }

        $dv = $d['kpis']['ventas']['delta'];
        if ($dv !== null && abs($dv) >= 0.05) {
            $sube = $dv > 0;
            $dt = $d['kpis']['ticket']['delta'];
            $do = $d['kpis']['ordenes']['delta'];
            $causa = '';
            if ($do !== null && $dt !== null) {
                $causa = abs($do) >= abs($dt)
                    ? ' Principalmente por ' . ($do > 0 ? 'más' : 'menos') . ' órdenes (' . $this->signo($do) . ').'
                    : ' Principalmente por un ticket ' . ($dt > 0 ? 'más alto' : 'más bajo') . ' (' . $this->signo($dt) . ').';
            }
            $out[] = [
                'tono' => $sube ? 'bueno' : 'alerta',
                'titulo' => 'Ventas ' . ($sube ? 'al alza' : 'a la baja') . ' ' . $this->signo($dv),
                'texto' => $money($act['netas']) . ' contra ' . $money($prev['netas']) . ' del periodo anterior.' . $causa,
            ];
        }

        $top = $d['estudios']['por_ventas'][0] ?? null;
        if ($top) {
            $e = $d['estudios'];
            $out[] = [
                'tono' => $top['participacion'] >= 0.35 ? 'alerta' : 'info',
                'titulo' => 'Estudio estrella: ' . $top['nombre'],
                'texto' => 'Aporta ' . $pct($top['participacion']) . ' de las ventas. '
                    . $e['pareto_80'] . ' de ' . $e['distintos'] . ' estudios generan el 80% del ingreso'
                    . ($top['participacion'] >= 0.35 ? '; hay dependencia alta de un solo estudio.' : '.'),
            ];
        }

        $m = $d['heatmap']['matriz'];
        $dias = ['lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'];
        $totDia = array_map('array_sum', $m);
        $best = array_search(max($totDia), $totDia);
        $horas = array_fill(0, 24, 0);
        foreach ($m as $fila) {
            foreach ($fila as $h => $n) {
                $horas[$h] += $n;
            }
        }
        $bh = array_search(max($horas), $horas);
        $out[] = [
            'tono' => 'info',
            'titulo' => 'Mayor afluencia: ' . $dias[$best] . ' ~' . $bh . ':00 h',
            'texto' => 'Según la hora de toma de muestra, el ' . $dias[$best] . ' concentra '
                . $pct($totDia[$best] / max(1, array_sum($totDia)))
                . ' de las órdenes. Refuerza personal en ese horario y programa promociones en los días flojos.',
        ];

        if ($act['recurrencia'] !== null) {
            $out[] = [
                'tono' => $act['recurrencia'] >= 0.3 ? 'bueno' : 'info',
                'titulo' => $pct($act['recurrencia']) . ' de pacientes regresan',
                'texto' => $act['pacientes_recurrentes'] . ' de ' . $act['pacientes'] . ' pacientes tienen más de una visita y '
                    . $act['pacientes_nuevos'] . ' vinieron por primera vez. Un recordatorio por WhatsApp para estudios de control puede subir la recurrencia.',
            ];
        }

        $med = $d['medicos'];
        if ($med['total_medicos'] > 0) {
            $tm = $med['top'][0];
            $out[] = [
                'tono' => 'info',
                'titulo' => $pct($med['pct_referidas']) . ' de órdenes vienen referidas',
                'texto' => $med['total_medicos'] . ' médicos refirieron pacientes; el principal es ' . $tm['nombre']
                    . ' (' . $tm['ordenes'] . ' órdenes, ' . $money($tm['ventas']) . ').',
            ];
        }

        if ($act['entrega_mediana_h'] !== null) {
            $h = $act['entrega_mediana_h'];
            $out[] = [
                'tono' => $h <= 24 ? 'bueno' : 'alerta',
                'titulo' => 'Entrega mediana: ' . $this->horas($h),
                'texto' => $pct($act['entrega_24h']) . ' de los resultados se validan en menos de 24 h desde la toma.',
            ];
        }

        if ($act['ordenes_sin_precio'] > 0) {
            $out[] = [
                'tono' => 'alerta',
                'titulo' => $act['ordenes_sin_precio'] . ' órdenes sin precio',
                'texto' => 'Hay estudios registrados en $0 que a precio de lista suman ' . $money($act['no_cobrado'])
                    . '. Si no son cortesías, revisa que se esté capturando el precio.',
            ];
        }

        $cot = $d['cotizaciones'];
        if ($cot['total'] > 0) {
            $out[] = [
                'tono' => $cot['conversion'] !== null && $cot['conversion'] >= 0.3 ? 'bueno' : 'info',
                'titulo' => $cot['total'] . ' cotizaciones por ' . $money($cot['monto_total']),
                'texto' => ($cot['conversion'] !== null
                        ? 'Aprox. ' . $pct($cot['conversion']) . ' de las que tienen nombre terminaron en orden. '
                        : '')
                    . ($cot['anonimas'] > 0 ? $cot['anonimas'] . ' se guardaron sin nombre del paciente, así que no se les puede dar seguimiento. ' : '')
                    . ($cot['sin_seguimiento'] >= $cot['total'] * 0.8 ? 'El estado casi nunca se actualiza en el sistema.' : ''),
            ];
        }

        if ($act['pendientes_validar'] > 0) {
            $out[] = [
                'tono' => 'alerta',
                'titulo' => $act['pendientes_validar'] . ' reportes sin validar',
                'texto' => 'Órdenes del periodo que aún no tienen fecha de validación.',
            ];
        }

        if ($act['brutas'] > 0 && $act['descuentos'] / $act['brutas'] >= 0.05) {
            $out[] = [
                'tono' => 'alerta',
                'titulo' => 'Descuentos: ' . $pct($act['descuentos'] / $act['brutas']) . ' de la venta',
                'texto' => 'Se otorgaron ' . $money($act['descuentos']) . ' en descuentos en el periodo.',
            ];
        }

        if ($act['cobertura_costo'] < 0.8) {
            $sinCosto = DB::table('estudios')->where(fn ($q) => $q->whereNull('costo')->orWhere('costo', '<=', 0))->count();
            $out[] = [
                'tono' => 'alerta',
                'titulo' => 'Margen incompleto',
                'texto' => 'Solo ' . $pct($act['cobertura_costo']) . ' de las ventas tiene costo capturado ('
                    . $sinCosto . ' estudios sin costo). Captúralo en Gestión de Estudios para medir la utilidad real.',
            ];
        }

        return $out;
    }

    private function signo(float $v): string
    {
        return ($v > 0 ? '+' : '') . number_format($v * 100, 0) . '%';
    }

    private function horas(float $h): string
    {
        return $h < 1 ? round($h * 60) . ' min' : ($h < 48 ? number_format($h, 1) . ' h' : number_format($h / 24, 1) . ' días');
    }
}
