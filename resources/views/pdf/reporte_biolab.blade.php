<html>
    <head>
        <style>
   
            @page {
                margin: 3.2cm 0.5cm 4.5cm 0.5cm;
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
                top: -3cm;
                left: 0cm;
                right: 0cm;
                height: 3cm;
            }

            .patient-block {
                margin: 0 0 8px 0;
                padding: 6px 8px;
                border: 1px solid #cbd5e0;
                border-radius: 4px;
                background-color: #f9fafb;
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
            
            .study-section {
                margin: 0 0 5px 0;
                page-break-inside: avoid;
                break-inside: avoid;
            }

            .study-block {
                page-break-inside: avoid;
                break-inside: avoid;
                margin-bottom: 10px;
            }
            
            .study-title {
                padding: 0px;
                margin: 0 0 8px 0;
                font-weight: bold;
                font-size: 12px;
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
                font-size: 11px;
                padding: 5px 2px !important;
            }

            .seccion-header td {
                border-top: 1px solid #08cc71;
                border-bottom: 1px solid #08cc71;
            }


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
            <img src="{{ public_path('img/imgbiolabfoot.png') }}" alt="">
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
                                    <img src="{{ public_path('img/imgbiolabtrans.png') }}" style="width: 65px;">
                                </td>
                                <td valign="top" style="padding-left: 6px;">
                                    <div style="font-weight: bold; font-size: 13px;">
                                        Laboratorio de Análisis Clínicos "BIOLAB"
                                    </div>
                                    <div style="font-size: 9px; margin-top: 2px;">Folio: <strong>{{ $reporte->folio }}</strong></div>
                                    <div style="margin-top: 1px;">
                                        <img src="{{public_path('img/codbarras.png') }}" style="width: 70px; height: 16px;" alt="">
                                    </div>
                                    <div style="font-size: 9px; margin-top: 2px;">
                                        Médico: <strong>{{ strtoupper($reporte->medico_solicitante ?: 'A QUIEN CORRESPONDA') }}</strong>
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
                            <div style="margin-top: 2px;"><strong>Fecha de Validación:</strong> {{ \Carbon\Carbon::parse($reporte->fecha_validacion)->format('d/m/Y g:i a') }}</div>
                        </div>
                    </td>
                </tr>
            </table>
            <hr style="width: 100%; border: 0.5px solid #000; margin: 2px 0 0 0; padding: 0;">
        </header>

        <footer>
                <div>
                    <p style="margin-left: 45px; font-size: 0.6rem; color: gray; font-style: italic; text-align: left; margin-bottom: -10px"> * Resultado fuera de rango</p>

                    <p style="margin-left: 45px; font-size: 0.6rem; color: gray; font-style: italic; text-align: left; margin-bottom: 5px;"> "Ciencia y compromiso al servicio de tu salud ".</p>
                    <table class="footer-table">
                        <tr>
                            {{-- QR --}}
                            <td style="width: 20%; text-align: center;">
                                <img src="{{ public_path('img/codigoqr.png') }}" width="80px" alt="QR">
                            </td>

                            {{-- Datos QR --}}
                            <td style="width: 30%;">
                                <img src="{{ public_path('img/firmados.png') }}" width="100px" alt="error firma">
                                <hr style="width: 100% ; border: 0.3px solid #000; margin: 0; padding: 0;">
                                <p style="margin: 0px"><strong>Q.F.B Ángel Augusto Pérez Arias</strong></p>
                                <p style="margin:0px"> Universidad popular de la chontalpa</p>
                                <p style="margin: 0px">Céd. Prof. 14083392</p>
                                <p style="margin: 0px">
                                    <img src="{{ public_path('img/whatsapp.png') }}" width="12px" alt=""> 923 235 1538
                                </p>
                            </td>

                            {{-- Logo transparente --}}
                            <td style="width: 20%; text-align: center;">
                                <img src="{{ public_path('img/imgbiolabtrans.png') }}" width="80px" alt="Biolab">
                            </td>

                            {{-- Datos Biolab --}}
                            <td style="width: 30%;">
                                <p style="margin: 0">Avenida Revolución, Región 75, Calle 37 Norte</p>
                                <p style="margin: 0">C.P. 77527, Cancún, Quintana Roo</p>
                                <p style="margin: 0">Tel: 923 235 1538</p>
                                <p style="margin: 0"><strong>biolab348@gmail.com</strong></p>
                            </td>

                        </tr>
                    </table>

                </div>
        </footer>

        <!-- Wrap the content of your PDF inside a main tag -->
        <main style="margin: 0; padding: 0;">
                <!-- Datos del paciente (solo aparece en la primera página) -->
                <div class="patient-block">
                    <table width="100%" style="font-size: 11px;">
                        <tr>
                            <td style="vertical-align: top; width: 55%; padding-right: 10px;">
                                <div><strong>Paciente/Patient:</strong> {{ strtoupper($reporte->nombre_cliente) }}</div>
                                <div style="margin-top: 2px;"><strong>Fecha de Nacimiento/Birthdate:</strong> {{ \Carbon\Carbon::parse($reporte->fecha_nacimiento)->format('d-m-Y') }}</div>
                                <div style="margin-top: 2px;"><strong>Sexo/Sex:</strong> {{ ucfirst($reporte->sexo) }}</div>
                            </td>
                            <td style="vertical-align: top; width: 45%;">
                                <div><strong>Correo/E-Mail:</strong> {{ strtoupper($reporte->email ?: 'NO ESPECIFICADO') }}</div>
                                <div style="margin-top: 2px;"><strong>Edad/Age:</strong> {{ $reporte->edad }} Años/Years old</div>
                            </td>
                        </tr>
                    </table>
                </div>

                <div>
                    @foreach ($reporte->estudios as $estudio)
                        <div class="study-block" @if(!$loop->first) style="margin-top: 8px;" @endif>

                        <div class="study-section">
                            <div class="study-title">{{ strtoupper($estudio->estudio->nombre) }}</div>
                            
                        

                            <table class="tablaExamenes">

                                <thead>
                                    <tr>
                                        <th style="width: 45%;">Examen</th>
                                        <th style="width: 15%;">Resultado</th>
                                        <th style="width: 15%;">Unidad</th>
                                        <th style="width: 25%;">Valor de referencia</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @php
                                        // Agrupar resultados por sección
                                        $resultadosPorSeccion = [];
                                        foreach ($estudio->resultados as $resultado) {
                                            $examen = $resultado->examen;
                                            $seccion = $examen->seccion ?? 'Sin Sección';
                                            
                                            if (!isset($resultadosPorSeccion[$seccion])) {
                                                $resultadosPorSeccion[$seccion] = [];
                                            }
                                            $resultadosPorSeccion[$seccion][] = $resultado;
                                        }
                                    @endphp

                                    @foreach ($resultadosPorSeccion as $nombreSeccion => $resultados)
                                        {{-- Mostrar encabezado de sección si hay una sección definida --}}
                                        @if($nombreSeccion !== 'Sin Sección' && count($resultadosPorSeccion) > 1)
                                            <tr class="seccion-header">
                                                <td colspan="4">{{ strtoupper($nombreSeccion) }}</td>
                                            </tr>
                                        @endif

                                        {{-- Mostrar los exámenes de la sección --}}
                                        @foreach ($resultados as $resultado)
                                            @php
                                                $examen = $resultado->examen;
                                                // Convertir explícitamente a booleano para asegurar la evaluación correcta
                                                $isOutOfRange = (bool) $resultado->fuera_rango;
                                                $hasAsterisk = strpos($resultado->resultado, '*') !== false;
                                            @endphp

                                            @if($hasAsterisk)
                                                @php
                                                    continue;
                                                @endphp
                                            @endif

                                            <tr style="{{ $isOutOfRange ? 'font-weight: bold; color: #000000;' : '' }}">
                                                <td class="exam-name">
                                                    {{ $examen->nombre_examen }}
                                                </td>
                                                <td class="result-value">
                                                    {{ $resultado->resultado }}
                                                    @if($isOutOfRange)
                                                        *
                                                    @endif
                                                </td>
                                                <td class="unit">{{ $examen->unidad }}</td>
                                                <td class="reference">{{ $examen->valor_referencia }}</td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>

                            </table>

                            <div class="study-details">
                                
                                <table width="100%" style="margin-top: 10px; font-size: 8px;">
                                    <tr>
                                        <!-- Columna 1: Elaboró y Validó -->
                                        <td style="vertical-align: top; width: 50%; padding-right: -20px;">
                                            <div>
                                                <span style="font-weight: 600">Validó: {{ $estudio->valido }}</span>
                                            </div>
                                        </td>

                                        <!-- Columna 2: Tipo de muestra y Método -->
                                        <td style="vertical-align: top; width: 50%; padding-left: 10px;">
                                            <div style="margin-bottom: 5px; margin-top: 2px;">
                                                <span>Tipo de muestra:</span> {{ strtoupper($estudio->tipo_muestra) }}
                                            </div>
                                            <div>
                                                <span>Método:</span> {{ strtoupper($estudio->metodo) }}
                                            </div>
                                        </td>
                                    </tr>

                                </table>
                                
                                
                            </div>
                        </div>

                        @if(!empty($estudio->observaciones))
                        <div style="margin-top: 8px; padding: 8px; background-color: #fffbeb; border-left: 3px solid #f59e0b; border-radius: 4px;">
                            <p style="margin: 0; font-size: 10px;">
                                <strong style="color: #f59e0b;">📝 Observaciones:</strong> 
                                <span style="color: #374151;">{{ $estudio->observaciones }}</span>
                            </p>
                        </div>
                        @endif

                        @if(!empty($estudio->estudio->leyenda))
                        <p style="text-align: center; font-size: 12px; font-style: italic; margin-top: 5px;">
                            {{ $estudio->estudio->leyenda }}
                        </p>
                        
                        @endif
                        </div>
                    @endforeach

                    
                </div>
        </main>
    </body>
</html>