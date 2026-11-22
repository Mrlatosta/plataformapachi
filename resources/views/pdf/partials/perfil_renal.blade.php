{{--
    T10: Perfil de función renal (TFGe y albuminuria) en una sola hoja.
    Sale cuando la orden trae cistatina C o albúmina en orina. Secciones en el
    orden del ticket; lo que no se capturó no aparece y no deja hueco.

    Variables: $reporte, $renal (FuncionRenal::analizar), $estudiosPerfil
    (estudios que se imprimen aquí en vez de en su tabla), $mantenerJuntos, $saltoAntes.
--}}
@use('App\Support\FuncionRenal')
@use('App\Support\RangoReferencia')
@php
    $v = $renal['valores'];
    $medido = function (string $clave) use ($renal) {
        return collect($renal['ubicados'])->first(fn ($u) => $u['clave'] === $clave && $u['valor'] !== null);
    };
    $filaMedida = function (?array $u, string $nombre) use ($reporte) {
        if (! $u) {
            return null;
        }
        $r = $u['resultado'];
        $ex = $r->examen;
        $niveles = $ex->niveles ?? collect();
        $fuera = (bool) $r->fuera_rango;
        $dir = $fuera ? (($r->direccion_rango ?? null) ?: RangoReferencia::direccion((string) $r->resultado, $ex->valor_referencia, $reporte->sexo, $reporte->edad, $niveles)) : null;
        $normal = $niveles->firstWhere('es_normal', true);

        return [
            'nombre' => $nombre,
            'texto' => (string) $r->resultado,
            'marca' => $fuera ? (RangoReferencia::flecha($dir) ?: '*') : '',
            'unidad' => $ex->unidad,
            'referencia' => $niveles->isNotEmpty() ? ($normal ? $normal->intervaloTexto() : 'Ver inserto') : ($ex->valor_referencia ?: 'Según inserto'),
        ];
    };

    $suero = array_values(array_filter([
        $filaMedida($medido('creatinina'), 'Creatinina'),
        $filaMedida($medido('cistatina'), 'Cistatina C'),
    ]));
    $orina = array_values(array_filter([
        $filaMedida($medido('albumina_orina'), 'Albúmina en orina'),
        $filaMedida($medido('creatinina_orina'), 'Creatinina en orina'),
    ]));
    $tfges = array_values(array_filter([
        $v['tfge_cr'] !== null ? ['nombre' => 'TFGe por creatinina', 'ecuacion' => 'CKD-EPI 2021', 'valor' => $v['tfge_cr'], 'resaltar' => false] : null,
        $v['tfge_cis'] !== null ? ['nombre' => 'TFGe por cistatina C', 'ecuacion' => 'CKD-EPI 2012', 'valor' => $v['tfge_cis'], 'resaltar' => false] : null,
        $v['tfge_comb'] !== null ? ['nombre' => 'TFGe combinada creatinina + cistatina C', 'ecuacion' => 'CKD-EPI 2021', 'valor' => $v['tfge_comb'], 'resaltar' => true] : null,
    ]));
    $G = $v['G'];
    $A = $v['A'];
@endphp

<div class="study-block perfil-renal {{ ($mantenerJuntos ?? true) ? 'sin-corte' : '' }}" @if($saltoAntes) style="page-break-before: always;" @endif>
    <div class="pr-titulo">FUNCIÓN RENAL · FILTRACIÓN GLOMERULAR ESTIMADA Y ALBUMINURIA</div>

    {{-- 1. Marcadores de función renal (suero) --}}
    @if(count($suero))
        <table class="pr-tabla">
            <tr><th style="width: 46%;">1. Marcadores de función renal (suero)</th><th style="width: 16%;">Resultado</th><th style="width: 14%;">Unidad</th><th>Referencia</th></tr>
            @foreach ($suero as $f)
                <tr>
                    <td class="{{ $f['marca'] ? 'fuera-rango' : '' }}">{{ $f['nombre'] }}</td>
                    <td class="{{ $f['marca'] ? 'fuera-rango' : '' }}">{{ $f['texto'] }}@if($f['marca'])<span class="flecha-rango">{{ $f['marca'] }}</span>@endif</td>
                    <td>{{ $f['unidad'] }}</td>
                    <td>{{ $f['referencia'] }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    {{-- 2. Filtración glomerular estimada (calculada) --}}
    @if(count($tfges))
        <table class="pr-tabla">
            <tr><th style="width: 46%;">2. Filtración glomerular estimada (calculada)</th><th style="width: 16%;">Resultado</th><th style="width: 14%;">Unidad</th><th>Referencia</th></tr>
            @foreach ($tfges as $t)
                <tr class="{{ $t['resaltar'] ? 'pr-resaltada' : '' }}">
                    <td class="{{ $t['valor'] < 60 ? 'fuera-rango' : '' }}">{{ $t['nombre'] }} <span class="pr-ecuacion">({{ $t['ecuacion'] }})</span></td>
                    <td class="{{ $t['valor'] < 60 ? 'fuera-rango' : '' }}">{{ $t['valor'] }}@if($t['valor'] < 60)<span class="flecha-rango">↓</span>@endif</td>
                    <td>{{ FuncionRenal::UNIDAD_TFGE }}</td>
                    <td>≥ 90</td>
                </tr>
            @endforeach
        </table>

        {{-- 3. Franja KDIGO G1 – G5 --}}
        <div class="pr-subtitulo">3. Categoría de filtración (KDIGO)</div>
        <table class="pr-franja">
            <tr>
                @foreach (FuncionRenal::CATEGORIAS_G as $c)
                    <td class="{{ ($G['codigo'] ?? null) === $c['codigo'] ? 'pr-actual' : '' }}">
                        <strong>{{ $c['codigo'] }}</strong><br>{{ $c['rango'] }}<br><span class="pr-chico">{{ $c['nombre'] }}</span>
                    </td>
                @endforeach
            </tr>
        </table>
    @endif

    {{-- 4. Albuminuria (orina) --}}
    @if(count($orina) || $v['rac'] !== null)
        <table class="pr-tabla">
            <tr><th style="width: 46%;">4. Albuminuria (orina al azar)</th><th style="width: 16%;">Resultado</th><th style="width: 14%;">Unidad</th><th>Referencia</th></tr>
            @foreach ($orina as $f)
                <tr>
                    <td class="{{ $f['marca'] ? 'fuera-rango' : '' }}">{{ $f['nombre'] }}</td>
                    <td class="{{ $f['marca'] ? 'fuera-rango' : '' }}">{{ $f['texto'] }}@if($f['marca'])<span class="flecha-rango">{{ $f['marca'] }}</span>@endif</td>
                    <td>{{ $f['unidad'] }}</td>
                    <td>{{ $f['referencia'] }}</td>
                </tr>
            @endforeach
            @if($v['rac'] !== null)
                <tr class="pr-resaltada">
                    <td class="{{ $v['rac'] >= 30 ? 'fuera-rango' : '' }}">Relación albúmina/creatinina (ACR) <span class="pr-ecuacion">(calculada)</span></td>
                    <td class="{{ $v['rac'] >= 30 ? 'fuera-rango' : '' }}">{{ number_format($v['rac'], 1, '.', '') }}@if($v['rac'] >= 30)<span class="flecha-rango">↑</span>@endif</td>
                    <td>{{ FuncionRenal::UNIDAD_RAC }}</td>
                    <td>&lt; 30</td>
                </tr>
            @endif
        </table>
        @if($v['rac'] !== null)
            <table class="pr-franja">
                <tr>
                    @foreach (FuncionRenal::CATEGORIAS_A as $c)
                        <td class="{{ ($A['codigo'] ?? null) === $c['codigo'] ? 'pr-actual' : '' }}">
                            <strong>{{ $c['codigo'] }}</strong> · {{ $c['rango'] }} mg/g<br><span class="pr-chico">{{ $c['nombre'] }}</span>
                        </td>
                    @endforeach
                </tr>
            </table>
        @endif
    @endif

    {{-- 5. Clasificación KDIGO combinada --}}
    @if($G || $A)
        <div class="pr-kdigo">
            <span class="pr-kdigo-etiqueta">5. Clasificación KDIGO</span>
            <span class="pr-kdigo-valor">{{ trim(($G['codigo'] ?? '').' '.($A['codigo'] ?? '')) }}</span>
            <span class="pr-kdigo-texto">
                @if($G) Filtración: {{ $G['nombre'] }}. @endif
                @if($A) Albuminuria: {{ $A['nombre'] }}. @endif
            </span>
        </div>
    @endif

    {{-- 6. Ecuaciones y observaciones fijas --}}
    <div class="pr-letra-chica">
        <strong>6. Ecuaciones:</strong>
        TFGe por creatinina: CKD-EPI 2021 sin coeficiente racial (142 × min(Cr/κ,1)^α × max(Cr/κ,1)^−1.200 × 0.9938^edad [× 1.012 mujer]).
        TFGe por cistatina C: CKD-EPI 2012 (133 × min(Cys/0.8,1)^−0.499 × max(Cys/0.8,1)^−1.328 × 0.996^edad [× 0.932 mujer]).
        Combinada: CKD-EPI 2021 creatinina-cistatina C. ACR (mg/g) = albúmina (mg/L) ÷ creatinina (mg/dL) × 100.
        <br><strong>Observaciones:</strong> {{ FuncionRenal::NOTA_TFGE }}
        @foreach (FuncionRenal::OBSERVACIONES_PERFIL as $obs) {{ $obs }} @endforeach
        @if($v['tfge_cr'] === null && $v['tfge_cis'] === null && ($v['creatinina'] !== null || $v['cistatina'] !== null))
            <br><strong>La TFGe no se calcula en menores de 18 años.</strong>
        @endif
    </div>

    {{-- Quién validó cada estudio de esta hoja --}}
    <table width="100%" style="margin-top: 6px; font-size: 8px; page-break-before: avoid;">
        @foreach ($estudiosPerfil as $e)
            <tr>
                <td style="width: 40%;"><strong>{{ mb_strtoupper($e->estudio->nombre ?? '') }}</strong></td>
                <td style="width: 30%;">
                    @if(! method_exists($e, 'estaValidado') || $e->estaValidado())
                        <strong>Validó: {{ $e->valido }}</strong>
                    @else
                        <strong style="color: #991b1b;">Pendiente de validación</strong>
                    @endif
                </td>
                <td>Muestra: {{ mb_strtoupper($e->tipo_muestra) }} · Método: {{ mb_strtoupper($e->metodo) }}</td>
            </tr>
            @if(trim((string) $e->observaciones) !== '')
                <tr><td colspan="3" class="observaciones-box"><strong>Observaciones:</strong> {!! nl2br(e(trim($e->observaciones))) !!}</td></tr>
            @endif
        @endforeach
    </table>
</div>
