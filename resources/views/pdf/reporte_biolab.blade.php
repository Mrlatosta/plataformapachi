<html>
    <head>
        <style>
   
            @page {
                margin: 4.3cm 0.5cm 4.5cm 0.5cm;
            }

            body {
                font-family: DejaVu Sans, sans-serif;
                font-size: 11px;
                margin: 0;
                padding: 0;
            }

            /** Define the header rules **/
            header {
                position: fixed;
                top: -4.1cm;
                left: 0cm;
                right: 0cm;
                height: 4.1cm;
            }

            .patient-row {
                margin-top: 3px;
                padding: 3px 5px;
                background-color: #f3f4f6;
                border: 1px solid #d1d5db;
                border-radius: 3px;
                font-size: 9px;
            }
            .patient-row td {
                padding: 1px 6px 1px 0;
            }

            /* Que las celdas de tbheader no tengan border */
            .tbheader td {
                border: none;
            }

            /** Define the footer rules **/
            footer {
                position: fixed;
                bottom: -4.3cm;
                left: 0cm;
                right: 0cm;
                height: 4.3cm;
            }

            
            .footer-table {
                width: 100%;
                border-collapse: collapse;
            }

            .footer-table td {
                vertical-align: top;
                font-size: 10px;
                padding: 5px;
            }
        

             
            .info-label {
                font-weight: bold;
                display: inline-block;
                width: 250px;
            }
            
            /*
             * T3: antes cada estudio completo llevaba page-break-inside: avoid. Cuando un
             * estudio era mas alto que la hoja, DomPDF lo mandaba entero a la pagina 2 y
             * dejaba la 1 en blanco. Ahora solo los estudios cortos (que caben en una hoja)
             * se mantienen juntos; los largos fluyen y repiten su encabezado (thead).
             */
            .study-block {
                margin-bottom: 10px;
            }

            .study-block.sin-corte {
                page-break-inside: avoid;
            }

            .tablaExamenes th.study-title {
                background-color: transparent;
                color: #000000;
                padding: 0 0 6px 0;
                font-weight: bold;
                font-size: 13px;
                text-align: left;
            }
            
            .study-details {
                margin: 5px 0;
                font-size: 10px;
            }
            
            .study-details .row {
                display: flex;
                margin-bottom: 3px;
            }
            
            .study-details .col {
                flex: 1;
                padding-right: 15px;
            }

            
            .tablaExamenes {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 3px;
                font-size: 13px;
            }

            .tablaExamenes th, .tablaExamenes td {
                padding: 2px;
                text-align: left;
            }

            .tablaExamenes thead {
                display: table-header-group;
            }

            .tablaExamenes tr {
                page-break-inside: avoid;
                break-inside: avoid;
            }
            
            /* Encabezado verde con texto blanco */
            .tablaExamenes th {
                background-color: #08cc71;
                color: white;
                font-weight: bold;
                font-size: 14px;
            }
            
    
            /* Alternar color en las filas de cada tabla */
            .tablaExamenes tbody tr:nth-child(odd) {
                background-color: #ffffff; /* Blanco */
            }

            .tablaExamenes tbody tr:nth-child(even) {
                background-color: #f8fdf9; /* Verde muy claro */
            }

            /* Estilo para encabezados de sección */
            .seccion-header {
                background-color: #e8f5e9 !important;
                font-weight: bold;
                font-size: 12px;
                padding: 5px 2px !important;
            }

            /* T4: resultado fuera de rango en rojo con flecha (numerico) o * (texto) */
            .fuera-rango {
                color: #c0392b;
                font-weight: bold;
            }

            .flecha-rango {
                font-weight: bold;
                font-size: 14px;
                padding-left: 3px;
                color: #c0392b;
            }

            .leyenda-flechas {
                margin-left: 45px;
                font-size: 8px;
                color: #4b5563;
                text-align: left;
                margin-bottom: -6px;
            }

            /* T7: notas a todo el ancho, alineadas a la izquierda y con ajuste de linea */
            .fila-nota td {
                text-align: left;
                font-size: 11px;
                line-height: 1.35;
                padding: 4px 2px;
            }

            .observaciones-box {
                margin-top: 6px;
                padding: 5px 8px;
                background-color: #fffbeb;
                border-left: 3px solid #f59e0b;
                text-align: left;
                font-size: 10px;
                line-height: 1.35;
                color: #374151;
                page-break-inside: avoid;
            }

            .leyenda-estudio {
                margin: 5px 0 0 0;
                text-align: left;
                font-size: 10px;
                font-style: italic;
                line-height: 1.35;
                color: #374151;
            }

            /* T5: tabla de niveles de referencia debajo del resultado */
            .fila-niveles td {
                padding: 0 2px 4px 2px;
            }

            .tabla-niveles {
                border-collapse: collapse;
                font-size: 9px;
                margin-left: 12px;
            }

            .tabla-niveles th {
                background-color: #f3f4f6;
                color: #374151;
                font-size: 9px;
                font-weight: bold;
                padding: 2px 8px;
                border: 0.5px solid #d1d5db;
            }

            .tabla-niveles td {
                padding: 2px 8px;
                border: 0.5px solid #d1d5db;
            }

            .tabla-niveles tr.nivel-actual td {
                background-color: #fef3c7;
                font-weight: bold;
            }

            .aviso-documento {
                margin-top: 3px;
                padding: 2px 6px;
                font-size: 9px;
                font-weight: bold;
                text-align: center;
                border-radius: 3px;
            }

            .aviso-preliminar {
                background-color: #fee2e2;
                color: #991b1b;
                border: 1px solid #fca5a5;
            }

            .aviso-complementario {
                background-color: #e0f2fe;
                color: #075985;
                border: 1px solid #7dd3fc;
            }

            .seccion-header td {
                border-top: 1px solid #08cc71;
                border-bottom: 1px solid #08cc71;
            }


            /* T10: hoja de perfil renal */
            .perfil-renal { font-size: 10px; }
            .pr-titulo { font-size: 13px; font-weight: bold; margin: 2px 0 6px 0; }
            .pr-subtitulo { font-size: 10px; font-weight: bold; margin: 6px 0 2px 0; }
            .pr-tabla { width: 100%; border-collapse: collapse; margin-bottom: 6px; font-size: 11px; }
            .pr-tabla th { background-color: #08cc71; color: white; text-align: left; padding: 3px 4px; font-size: 11px; }
            .pr-tabla td { padding: 3px 4px; border-bottom: 0.5px solid #e5e7eb; }
            .pr-resaltada td { background-color: #ecfdf5; font-weight: bold; }
            .pr-ecuacion { font-size: 8px; font-weight: normal; color: #6b7280; }
            .pr-franja { width: 100%; border-collapse: collapse; margin-bottom: 6px; table-layout: fixed; }
            .pr-franja td { border: 0.5px solid #d1d5db; text-align: center; font-size: 9px; padding: 3px 2px; vertical-align: top; }
            .pr-franja td.pr-actual { background-color: #fef3c7; border: 1.5px solid #f59e0b; }
            .pr-chico { font-size: 7.5px; color: #4b5563; }
            .pr-kdigo { border: 1.5px solid #08cc71; border-radius: 4px; padding: 5px 8px; margin: 4px 0 6px 0; }
            .pr-kdigo-etiqueta { font-weight: bold; font-size: 10px; }
            .pr-kdigo-valor { font-weight: bold; font-size: 16px; margin: 0 10px; }
            .pr-kdigo-texto { font-size: 9px; color: #374151; }
            .pr-letra-chica { font-size: 7.5px; line-height: 1.3; color: #374151; text-align: justify; }

            .watermark {
                position: fixed;
                top: 40%;
                left: 50%;
                transform: translate(-50%, -50%);
                opacity: 0.10;
                z-index: -100;
                width: 500px;
                text-align: center;
            }
            .watermark img {
                width: 500px;
            }
            

        </style>
    </head>
    <body>
        <!-- Watermark de fondo -->
        <div class="watermark">
            <img src="{{ public_path('img/pdf/imgbiolabfoot.png') }}" alt="">
        </div>

        <!-- Define header and footer blocks before your content -->
        <header>
            <table width="100%" style="font-size: 9px; border: none" class="tbheader">
                <tr>
                    <!-- LOGO Y DATOS -->
                    <td width="60%" valign="top">
                        <table>
                            <tr>
                                <td valign="top" style="width: 70px;">
                                    <img src="{{ public_path('img/pdf/imgbiolabtrans.png') }}" style="width: 65px;">
                                </td>
                                <td valign="top" style="padding-left: 6px;">
                                    <div style="font-weight: bold; font-size: 13px;">
                                        Laboratorio de Análisis Clínicos "BIOLAB"
                                    </div>
                                    <div style="font-size: 9px; margin-top: 2px;">Folio: <strong>{{ $reporte->folio }}</strong></div>
                                    <div style="margin-top: 1px;">
                                        <img src="{{public_path('img/pdf/codbarras.png') }}" style="width: 70px; height: 16px;" alt="">
                                    </div>
                                    <div style="font-size: 9px; margin-top: 2px;">
                                        Médico: <strong>{{ mb_strtoupper($reporte->medico_solicitante ?: 'A QUIEN CORRESPONDA') }}</strong>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>

                    <!-- DATOS DE FECHA -->
                    <td width="40%" valign="top">
                        <div style="background-color: #e9f0fe; padding: 4px 6px; font-size: 9px; border: 1px solid #cbd5e0; border-radius: 4px;">
                            <div><strong>Toma de muestra:</strong> {{ \Carbon\Carbon::parse($reporte->toma_muestra)->format('d/m/Y g:i a') }}</div>
                            <div style="margin-top: 2px;"><strong>Fecha de Reporte:</strong> {{ \Carbon\Carbon::parse($reporte->fecha_reporte)->format('d/m/Y g:i a') }}</div>
                            <div style="margin-top: 2px;"><strong>Fecha de Validación:</strong> {{ $reporte->fecha_validacion ? \Carbon\Carbon::parse($reporte->fecha_validacion)->format('d/m/Y g:i a') : 'Pendiente' }}</div>
                        </div>
                    </td>
                </tr>
            </table>
            <!-- Datos del paciente (en todas las páginas) -->
            <div class="patient-row">
                <table width="100%">
                    <tr>
                        <td><strong>Paciente:</strong> {{ mb_strtoupper($reporte->nombre_cliente) }}</td>
                        <td><strong>Edad:</strong> {{ $reporte->edad }} años</td>
                        <td><strong>Sexo:</strong> {{ ucfirst($reporte->sexo) }}</td>
                    </tr>
                    <tr>
                        <td><strong>F. Nacimiento:</strong> {{ $reporte->fecha_nacimiento ? \Carbon\Carbon::parse($reporte->fecha_nacimiento)->format('d-m-Y') : 'NO ESPECIFICADA' }}</td>
                        <td colspan="2"><strong>E-Mail:</strong> {{ mb_strtoupper($reporte->email ?: 'NO ESPECIFICADO') }}</td>
                    </tr>
                </table>
            </div>
            <hr style="width: 100%; border: 0.5px solid #000; margin: 3px 0 0 0; padding: 0;">
        </header>

        <footer>
                <div>
                    <p class="leyenda-flechas">
                        <span class="flecha-rango">↑ ↓</span> Fuera del intervalo de referencia
                        &nbsp;&nbsp;
                        <span class="flecha-rango">*</span> Resultado distinto al valor normal
                    </p>

                    <p style="margin-left: 45px; font-size: 0.6rem; color: gray; font-style: italic; text-align: left; margin-bottom: 5px;"> "Ciencia y compromiso al servicio de tu salud ".</p>
                    <table class="footer-table">
                        <tr>
                            {{-- QR --}}
                            <td style="width: 20%; text-align: center;">
                                @php
                                    $qrVerificacion = \App\Support\QrVerificacion::dataUri($reporte->token_verificacion ?? null);
                                @endphp
                                @if ($qrVerificacion)
                                    <img src="{{ $qrVerificacion }}" width="80px" alt="QR de verificación">
                                @else
                                    {{-- Vista previa: el token se asigna hasta guardar el reporte --}}
                                    <div style="width: 80px; height: 80px; margin: 0 auto; border: 1px dashed #999; font-size: 7px; color: #999; text-align: center; line-height: 1.4; padding-top: 28px; box-sizing: border-box;">QR disponible<br>al guardar</div>
                                @endif
                            </td>

                            {{-- Datos QR --}}
                            <td style="width: 30%;">
                                <img src="{{ public_path('img/pdf/firmados.png') }}" width="100px" alt="error firma">
                                <hr style="width: 100% ; border: 0.3px solid #000; margin: 0; padding: 0;">
                                <p style="margin: 0px"><strong>Q.F.B Ángel Augusto Pérez Arias</strong></p>
                                <p style="margin:0px"> Universidad popular de la chontalpa</p>
                                <p style="margin: 0px">Céd. Prof. 14083392</p>
                                <p style="margin: 0px">
                                    <img src="{{ public_path('img/pdf/whatsapp.png') }}" width="12px" alt=""> 998 654 3958
                                </p>
                            </td>

                            {{-- Logo transparente --}}
                            <td style="width: 20%; text-align: center;">
                                <img src="{{ public_path('img/pdf/imgbiolabtrans.png') }}" width="80px" alt="Biolab">
                            </td>

                            {{-- Datos Biolab --}}
                            <td style="width: 30%;">
                                <p style="margin: 0">Av. 103, Mz. 116, Lt. 29</p>
                                <p style="margin: 0">Cancún, Quintana Roo</p>
                                <p style="margin: 0">Tel: 998 654 3958</p>
                                <p style="margin: 0"><strong>biolab348@gmail.com</strong></p>
                            </td>

                        </tr>
                    </table>

                </div>
        </footer>

        @if(!empty($preliminar))
            {{-- Marca en cada hoja: hay estudios que el Q.F.B. aun no valida --}}
            <div style="position: fixed; top: 35%; left: 0; right: 0; text-align: center; font-size: 90px; font-weight: bold; color: #dc2626; opacity: 0.08; z-index: 1000;">
                PRELIMINAR
            </div>
        @endif

        <main style="margin: 0; padding: 0;">
            @if(!empty($complementario))
                <div class="aviso-documento aviso-complementario">
                    REPORTE COMPLEMENTARIO · Mismo folio {{ $reporte->folio }}
                </div>
            @endif
            @if(!empty($preliminar))
                <div class="aviso-documento aviso-preliminar">
                    RESULTADO PRELIMINAR · Contiene estudios pendientes de validación. No entregar al paciente.
                </div>
            @endif

            @php
                $hayResultados = $reporte->estudios->contains(fn ($e) => collect($e->resultados)
                    ->contains(fn ($r) => trim((string) $r->resultado) !== '' && strpos((string) $r->resultado, '*') === false));
            @endphp
            @php
                // T10: TFGe / RAC calculados. Con cistatina C o albuminuria sale la hoja
                // de perfil renal; los estudios que solo traen analitos renales se
                // imprimen ahi (no en su tabla) para no repetirlos.
                $renal = \App\Support\FuncionRenal::analizar($reporte);
                $conPerfilRenal = $renal['valores']['cistatina'] !== null || $renal['valores']['albumina_orina'] !== null;
                $estudiosPerfil = $conPerfilRenal
                    ? $reporte->estudios->filter(function ($e) use ($renal) {
                        $conValor = collect($e->resultados)->filter(fn ($r) => trim((string) $r->resultado) !== '' && strpos((string) $r->resultado, '*') === false);
                        $renales = collect($renal['ubicados'])->filter(fn ($u) => $u['estudio'] === $e)->count();
                        return $conValor->isNotEmpty() && $conValor->count() === $renales;
                    })
                    : collect();
                $impresos = 0;
            @endphp

            @unless($hayResultados)
                {{-- Orden sin nada capturado: en vez de una hoja vacia, un aviso --}}
                <div class="study-block" style="margin-top: 30px; text-align: center; font-size: 12px; color: #6b7280;">
                    Esta orden aún no tiene resultados capturados.
                </div>
            @endunless

            @foreach ($reporte->estudios as $estudio)
                @php
                    // Un '*' capturado como resultado significa que ese examen no se imprime
                    $visibles = collect($estudio->resultados)
                        ->filter(fn ($r) => strpos((string) $r->resultado, '*') === false)
                        ->values();

                    // Estudios cortos se mantienen en una hoja; los largos pueden partirse
                    // entre renglones (nunca a la mitad de uno) y repiten su encabezado.
                    // Se estima la altura por lineas (no por renglones): una referencia de
                    // 3 lineas triplica el renglon. Si la estimacion falla, ReportePdf lo
                    // detecta y vuelve a generar con $mantenerJuntos = false.
                    $lineas = fn ($texto, $ancho) => collect(preg_split('/\R/u', trim((string) $texto)))
                        ->sum(fn ($l) => max(1, (int) ceil(mb_strlen($l) / $ancho)));
                    $alturaPx = 70; // titulo + encabezado de columnas + Validó/método
                    foreach ($visibles as $r) {
                        $niv = count($r->examen->niveles ?? []);
                        $alturaPx += 6 + 20 * max(
                            $lineas($r->examen->nombre_examen, 42),
                            // Texto largo sin unidad ni referencia sale como nota a todo el ancho (T7)
                            $lineas($r->resultado, trim((string) $r->examen->unidad) === '' && trim((string) $r->examen->valor_referencia) === '' && mb_strlen((string) $r->resultado) > 30 ? 110 : 14),
                            $lineas($r->examen->valor_referencia, 24),
                        ) + ($niv ? 18 * ($niv + 1) + 8 : 0);
                    }
                    if (! $conPerfilRenal) {
                        foreach ($visibles as $r) {
                            $alturaPx += count($renal['filas'][\App\Support\FuncionRenal::claveResultado($r)] ?? []) * 175;
                        }
                    }
                    $alturaPx += trim((string) $estudio->observaciones) !== '' ? 14 * $lineas($estudio->observaciones, 120) + 16 : 0;
                    $alturaPx += ! empty($estudio->estudio->leyenda) ? 14 * $lineas($estudio->estudio->leyenda, 120) + 8 : 0;
                    // Cuerpo de una hoja carta con estos margenes: ~720px
                    $cabeEnUnaHoja = ($mantenerJuntos ?? true) && $alturaPx <= config('reportes.altura_util_px', 620);

                    $porSeccion = $visibles->groupBy(fn ($r) => ($r->examen->seccion ?? null) ?: 'Sin Sección');
                    $sexo = $reporte->sexo;
                    $edad = $reporte->edad;

                    // Un estudio sin ningun resultado escrito no imprime una tabla vacia
                    $sinResultados = $visibles->every(fn ($r) => trim((string) $r->resultado) === '');
                @endphp

                @continue($sinResultados)
                @continue($estudiosPerfil->contains(fn ($e) => $e === $estudio))
                @php $impresos++; @endphp

                <div class="study-block {{ $cabeEnUnaHoja ? 'sin-corte' : '' }}" @if(!$loop->first) style="margin-top: 8px;" @endif>
                    <table class="tablaExamenes">
                        <thead>
                            <tr>
                                <th colspan="4" class="study-title">{{ mb_strtoupper($estudio->estudio->nombre) }}</th>
                            </tr>
                            <tr>
                                <th style="width: 45%;">Examen</th>
                                <th style="width: 15%;">Resultado</th>
                                <th style="width: 15%;">Unidad</th>
                                <th style="width: 25%;">Valor de referencia</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($porSeccion as $nombreSeccion => $resultados)
                                @if($nombreSeccion !== 'Sin Sección' && $porSeccion->count() > 1)
                                    <tr class="seccion-header">
                                        <td colspan="4">{{ mb_strtoupper($nombreSeccion) }}</td>
                                    </tr>
                                @endif

                                @foreach ($resultados as $resultado)
                                    @php
                                        $examen = $resultado->examen;
                                        $valorResultado = (string) $resultado->resultado;
                                        $niveles = $examen->niveles ?? collect();
                                        $fueraRango = (bool) $resultado->fuera_rango;

                                        // Flecha: la guardada o, si no hay, la deducida contra la referencia
                                        // del sexo/edad del paciente. Sin flecha (texto) se marca con *.
                                        $direccionRango = $fueraRango
                                            ? (($resultado->direccion_rango ?? null) ?: \App\Support\RangoReferencia::direccion($valorResultado, $examen->valor_referencia, $sexo, $edad, $niveles))
                                            : null;
                                        $marca = $fueraRango ? (\App\Support\RangoReferencia::flecha($direccionRango) ?: '*') : '';

                                        // T7: observaciones y textos largos sin unidad ni referencia van a todo el ancho
                                        $esNota = trim($valorResultado) !== ''
                                            && (preg_match('/^\s*observaci/iu', $examen->nombre_examen)
                                                || (trim((string) $examen->unidad) === '' && trim((string) $examen->valor_referencia) === '' && mb_strlen($valorResultado) > 30));

                                        $normal = $niveles->firstWhere('es_normal', true);
                                        $referencia = $niveles->isNotEmpty()
                                            ? ($normal ? $normal->intervaloTexto() : 'Ver tabla')
                                            : $examen->valor_referencia;
                                    @endphp

                                    @if($esNota)
                                        <tr class="fila-nota">
                                            <td colspan="4">
                                                <strong>{{ rtrim(trim($examen->nombre_examen), ':.') }}:</strong>
                                                {!! nl2br(e(trim($valorResultado))) !!}
                                            </td>
                                        </tr>
                                    @else
                                        <tr>
                                            <td class="exam-name {{ $fueraRango ? 'fuera-rango' : '' }}">{{ $examen->nombre_examen }}</td>
                                            <td class="result-value {{ $fueraRango ? 'fuera-rango' : '' }}">
                                                {{ $valorResultado }}@if($marca !== '')<span class="flecha-rango">{{ $marca }}</span>@endif
                                            </td>
                                            <td class="unit">{{ $examen->unidad }}</td>
                                            <td class="reference">{{ $referencia }}</td>
                                        </tr>

                                        @if($niveles->isNotEmpty())
                                            <tr class="fila-niveles">
                                                <td colspan="4">
                                                    <table class="tabla-niveles">
                                                        <tr>
                                                            <th>Intervalo</th>
                                                            <th>Interpretación</th>
                                                        </tr>
                                                        @php $valorNumerico = is_numeric(str_replace(',', '.', trim($valorResultado))) ? (float) str_replace(',', '.', trim($valorResultado)) : null; @endphp
                                                        @foreach($niveles as $nivel)
                                                            @php $esActual = $nivel->contiene($valorNumerico); @endphp
                                                            <tr class="{{ $esActual ? 'nivel-actual' : '' }}">
                                                                <td>{{ $nivel->intervaloTexto() }}</td>
                                                                <td>{{ $nivel->interpretacion }}@if($esActual) &nbsp;◄ Resultado @endif</td>
                                                            </tr>
                                                        @endforeach
                                                    </table>
                                                </td>
                                            </tr>
                                        @endif

                                        @php $filasRenales = $conPerfilRenal ? [] : ($renal['filas'][\App\Support\FuncionRenal::claveResultado($resultado)] ?? []); @endphp
                                        @if(count($filasRenales))
                                            {{-- T10: TFGe con su tabla G1–G5 y la nota fija, debajo de la creatinina --}}
                                            @include('pdf.partials.renal_filas', ['filas' => $filasRenales])
                                        @endif
                                    @endif
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>

                    {{-- No se queda solo en otra hoja: se lleva consigo el ultimo renglon --}}
                    <table width="100%" style="margin-top: 8px; font-size: 8px; page-break-before: avoid;">
                        <tr>
                            <td style="vertical-align: top; width: 50%;">
                                @if(! method_exists($estudio, 'estaValidado') || $estudio->estaValidado())
                                    <span style="font-weight: bold;">Validó: {{ $estudio->valido }}</span>
                                @else
                                    <span style="font-weight: bold; color: #991b1b;">Pendiente de validación</span>
                                @endif
                            </td>
                            <td style="vertical-align: top; width: 50%; padding-left: 10px;">
                                <div style="margin-bottom: 3px;">Tipo de muestra: {{ mb_strtoupper($estudio->tipo_muestra) }}</div>
                                <div>Método: {{ mb_strtoupper($estudio->metodo) }}</div>
                            </td>
                        </tr>
                    </table>

                    @if(trim((string) $estudio->observaciones) !== '')
                        <div class="observaciones-box">
                            <strong>Observaciones:</strong> {!! nl2br(e(trim($estudio->observaciones))) !!}
                        </div>
                    @endif

                    @if(!empty($estudio->estudio->leyenda))
                        <p class="leyenda-estudio">{{ $estudio->estudio->leyenda }}</p>
                    @endif
                </div>
            @endforeach

            @if($conPerfilRenal)
                @include('pdf.partials.perfil_renal', ['saltoAntes' => $impresos > 0])
            @endif
        </main>
    </body>
</html>