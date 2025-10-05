<html>
    <head>
        <style>
   
            @page {
                margin: 0cm 0.5cm;
            }

            body {
                font-family: DejaVu Sans, sans-serif;
                font-size: 11px;
                margin-top: 6cm;
                margin-left: 0.5cm;
                margin-right: 0.5cm;
                margin-bottom: 3cm;
            }

            /** Define the header rules **/
            header {
                position: fixed;
                top: 0cm;
                left: 0cm;
                right: 0cm;
                height: 6cm;

                /** Extra personal styles **/
                /* background-color: #03a9f4;
                color: white;
                text-align: center; */
                /* line-height: 1.5cm; */
            }

                    /* Que las celdas de tbheader no tengan border */
                .tbheader td {
                    border: none;
                }

            /** Define the footer rules **/
            footer {
                position: fixed; 
                bottom: 0cm; 
                left: 0cm; 
                right: 0cm;
                height: 6cm;

                /** Extra personal styles **/
                /* background-color: #03a9f4; */
                /* color: white;
                text-align: center;
                line-height: 1.5cm; */
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
            margin: 5px 0;
            page-break-inside: avoid;
        }
        
        .study-title {
          
            padding: 0px;
            margin: 5px 0 8px 0;
            font-weight: bold;
            font-size: 10px;
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
            margin-bottom: 5px;
            font-size: 14px;
        }
        .tablaExamenes th, .tablaExamenes td {
            /* border: 1px solid #000; */
            padding: 2px;
            text-align: left;
        }
        .tablaExamenes th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        body::before {
    content: "";
    position: fixed;
    top: 25%;
    left: 0;
    width: 100%;
    height: 100%;
    background: url("{{ public_path('img/imgbiolabfoot.png') }}") no-repeat center center;
    background-size: 600px;
    opacity: 0.15;
    z-index: -100;
}
        

        </style>
    </head>
    <body>
        <!-- Define header and footer blocks before your content -->
        <header>
            <table width="100%" style="margin-top: 10px; font-size: 10px; border: none" class="tbheader">
                <tr>
                    <!-- LOGO Y DATOS -->
                    <td width="70%" valign="top">
                    <table>
                        <tr>
                        <td>
                            <img src="{{ public_path('img/imgbiolabtrans.png') }}" style="width: 100px; margin-right: 10px;">
                        </td>
                        <td style="padding-left: 10px;">
                            <div style="font-weight: bold; font-size: 15px;">
                            Laboratorio de Análisis Clínicos "BIOLAB"
                            </div>
                            <div style="font-size: 10px;">
                            <div>Folio: {{ $reporte->folio }}</div>
                            <div><img src="{{public_path('img/codbarras.png') }}" style="width: 80px; height: 20px;" alt=""></div>
                            <div style="margin-top: 5px;">
                                Médico solicitante: 
                                <strong>{{ strtoupper($reporte->medico_solicitante ?: 'A QUIEN CORRESPONDA') }}</strong>
                            </div>
                            </div>
                        </td>
                        </tr>
                    </table>
                    </td>

                    <!-- DATOS DE FECHA -->
                    <td width="30%" valign="top">
                    <div style="background-color: #e9f0fe; padding: 10px 15px; font-size: 10px; border: 1px solid #cbd5e0; border-radius: 5px;">
                        <div><strong>Toma de muestra:</strong><br>{{ \Carbon\Carbon::parse($reporte->toma_muestra)->format('d/m/Y g:i a') }}</div>
                        <div style="margin-top: 5px;"><strong>Fecha de Reporte:</strong><br>{{ \Carbon\Carbon::parse($reporte->fecha_reporte)->format('d/m/Y g:i a') }}</div>
                        <div style="margin-top: 5px;"><strong>Fecha de Validación:</strong><br>{{ \Carbon\Carbon::parse($reporte->fecha_validacion)->format('d/m/Y g:i a') }}</div>
                    </div>
                    </td>
                </tr>
                </table>
        




        <!-- Contenido principal -->
                <div class="patient-info">
                    <table width="100%" style="font-size: 12px;">
                        <tr>
                            <!-- Columna izquierda -->
                            <td style="vertical-align: top; width: 50%; padding-right: 10px;">
                                <div style="margin-bottom: 5px;">
                                    <span > <strong>Paciente/Patient: </strong></span> {{ strtoupper($reporte->nombre_cliente) }}
                                </div>
                                <div style="margin-bottom: 5px;">
                                    <span > <strong>Fecha de Nacimiento/Birthdate:</strong></span> {{ \Carbon\Carbon::parse($reporte->fecha_nacimiento)->format('d-m-Y') }}
                                </div>
                                <div>
                                    <span > <strong>Sexo/Sex:</strong></span> {{ ucfirst($reporte->sexo) }}
                                </div>
                            </td>

                            <!-- Columna derecha -->
                            <td style="vertical-align: top; width: 50%; padding-left: 10px;">
                                <div style="margin-bottom: 5px;">
                                    <span > <strong>Correo/E-Mail:</strong></span> {{ strtoupper($reporte->email ?: 'NO ESPECIFICADO') }}
                                </div>
                                <div>
                                    <span > <strong>Edad/Age:</strong></span> {{ $reporte->edad }} Años/Years old
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>

                    <hr style="width: 100% ; border: 1px solid #000; margin: 0; padding: 0;">
        </header>

        <footer>

            
                <div>
                    <p style="margin-left: 45px; font-size: 0.6rem; color: gray; font-style: italic; text-align: left; margin-bottom: -10px"> * Resultado fuera de rango</p>

                    <p style="margin-left: 45px; font-size: 0.6rem; color: gray; font-style: italic; text-align: left"> "Ciencia y compromiso al servicio de tu salud ".</p>
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
        <main>
            <main>
                
             {{-- <div style="
                position: fixed;
                left: 0;
                bottom: 250px;
                width: 100%;
                text-align: center;
                opacity: 0.3;
                z-index: -100;
            ">
                <img src="{{ public_path('img/imgbiolabfoot.png') }}" style="width: 600px;" alt="Biolab">
            </div> --}}

                <div >
                        @foreach ($reporte->estudios as $estudio)

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
                                        @foreach ($estudio->resultados as $resultado)
                                            @php
                                                $examen = $resultado->examen;
                                                $isOutOfRange = $resultado->fuera_rango;
                                                $hasDash = strpos($resultado->resultado, '-') !== false; // Verifica si contiene "-"
                                                $hasAsterisk = strpos($resultado->resultado, '*') !== false; // Verifica si contiene "*"
                                            @endphp

                                            @if($hasAsterisk)
                                                @php
                                                    //Si tiene asterisco, no incluir el <tr></tr>  ysaltar al otro
                                                    continue;
                                                @endphp
                                            @endif


                                            <tr style="{{ $isOutOfRange ? 'font-weight: bold; color: #000000;' : '' }}">
                                                <td class="exam-name">
                                                    @if($hasDash)
                                                        <strong>{{ $examen->nombre_examen }}</strong>
                                                    @else
                                                        {{ $examen->nombre_examen }}
                                                    @endif
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
                                    </tbody>

                                </table>

                                <div class="study-details">
                                    
                                    <table width="100%" style="margin-top: 10px; font-size: 8px;">
                                        <tr>
                                            <!-- Columna 1: Elaboró y Validó -->
                                            <td style="vertical-align: top; width: 50%; padding-right: -20px;">
                                                {{-- <div style="margin-bottom: 5px;">
                                                    <span class="info-label">Elaboró:</span> {{ $estudio->elaboro }}
                                                </div> --}}
                                                <div>
                                                    <span style="font-weight: 600"">Validó: {{ $estudio->valido }}</span>
                                                </div>
                                            </td>

                                            <!-- Columna 2: Tipo de muestra y Método -->
                                            <td style="vertical-align: top; width: 50%; padding-left: 10px;">
                                                <div style="margin-bottom: 5px; margin-top: 2px;">
                                                    <span class="">Tipo de muestra:</span> {{ strtoupper($estudio->tipo_muestra) }}
                                                </div>
                                                <div>
                                                    <span class="">Método:</span> {{ strtoupper($estudio->metodo) }}
                                                </div>
                                            </td>
                                        </tr>

                                    </table>
                                    
                                    
                                </div>
                            </div>

                            @if(!empty($estudio->estudio->leyenda))
                            <p style="text-align: center; font-size: 12px; font-style: italic; margin-top: 5px;">
                                {{ $estudio->estudio->leyenda }}
                            </p>
                            
                            @endif
                        @endforeach

                        
                </div>
            </main>
        </main>
    </body>
</html>