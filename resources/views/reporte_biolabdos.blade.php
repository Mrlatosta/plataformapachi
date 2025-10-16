<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 0cm 0cm; /* top, right, bottom, left */

            /* @top-center {
                content: element(header);
            }

            @bottom-center {
                content: element(footer);
            } */
        }
        
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            margin-top: 2cm; /* espacio para el header */
            margin-bottom: 2cm; /* espacio para el footer */
            margin-left: 2cm;
            margin-right: 2cm;
        }
        
        header {
                /* position: running(header); */

        position: fixed;
        top: 0cm;
        left: 0cm;
        right: 0cm;
        height: 2cm;
        /* text-align: center; */
         }

    footer {
            /* position: running(footer); */

        position: fixed;
        top:0cm;
        left: 0;
        right: 0;
        height: 2cm;
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
        
        .logo { 
            width: 100px; 
            margin: 0 auto; 
        }
        
        /* .header h2 {
            margin: 5px 0;
            font-size: 14px;
            font-weight: bold;
        }
        
        .header .slogan {
            font-style: italic;
            font-size: 10px;
            margin: 5px 0;
            color: #666;
        } */
        
       
        
        
        
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 250px;
        }
        
        .study-section {
            margin: 10px 0;
            page-break-inside: avoid;
        }
        
        .study-title {
          
            padding: 0px;
            margin: 10px 0 8px 0;
            font-weight: bold;
            font-size: 10px;
            text-align: left;
        }
        
        .study-details {
            margin: 10px 0;
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
            margin-bottom: 15px;
            font-size: 10px;
        }
        .tablaExamenes th, .tablaExamenes td {
            /* border: 1px solid #000; */
            padding: 6px;
            text-align: left;
        }
        .tablaExamenes th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        
        
        /* table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 15px 0;
            font-size: 10px;
        }
        
        th {
            background-color: #343a40;
            color: white;
            border: 1px solid #000;
            padding: 8px 5px;
            text-align: center;
            font-weight: bold;
        }
        
        td { 
            border: 1px solid #000; 
            padding: 6px 5px;
            vertical-align: top;
        } */
        
        .exam-name {
            font-weight: normal;
            text-align: left;
        }
        
        .result-value {
            text-align: center;
            font-weight: bold;
        }
        
        .unit {
            text-align: center;
        }
        
        .reference {
            text-align: center;
            font-size: 9px;
        }
        
        .out-of-range {
            font-weight: bold;
        }
        
        .footer-signature {
            text-align: center;
            font-size: 10px;
        }
        
        
        
        .center { 
            text-align: center; 
        }

        /* Que las celdas de tbheader no tengan border */
        .tbheader td {
            border: none;
        }
        
        
        /* Para evitar que las tablas se corten en el medio */
        /* table {
            page-break-inside: auto;
        }
        
        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        } */
        


        .wrapper {
    margin-top: 10px; /* igual o mayor al height del header */
}
    </style>
</head>


<body>
<!-- Header fijo -->
    <!-- Encabezado tipo Nazareth -->
    

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
                <table width="100%" style="font-size: 11px;">
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

    {{-- <div style="height: 250px;"></div> <!-- Espacio para el header --> --}}

    
<footer>
    
    <div>
    <p style="margin-left: 45px; font-size: 0.6rem; color: gray; font-style: italic; text-align: left"> * Resultado fuera de rango</p>

    <p style="margin-left: 45px; font-size: 0.6rem; color: gray; font-style: italic; text-align: left"> "Ciencia y compromiso al servicio de tu salud ".</p>
    <table class="footer-table">
        <tr>
            {{-- QR --}}
            <td style="width: 20%; text-align: center;">
                <img src="{{ public_path('img/codigoqr.png') }}" width="80px" alt="QR">
            </td>

            {{-- Datos QR --}}
            <td style="width: 30%;">
                <p><strong>Q.F.B Ángel Augusto Pérez Arias</strong></p>
                <p>Céd. Prof. 14083392</p>
                <p>
                    <img src="{{ public_path('img/whatsapp.png') }}" width="12px" alt=""> 923 235 1538
                </p>
            </td>

            {{-- Logo transparente --}}
            <td style="width: 20%; text-align: center;">
                <img src="{{ public_path('img/imgbiolabtrans.png') }}" width="80px" alt="Biolab">
            </td>

            {{-- Datos Biolab --}}
            <td style="width: 30%;">
                <p style="margin: 0"><strong>Q.F.B Ángel Augusto Pérez Arias</strong></p>
                <p style="margin: 0">Cédula Prof. 14083392</p>
                <p style="margin: 0">Avenida Revolución, Región 75, Calle 37 Norte</p>
                <p style="margin: 0">C.P. 77527, Cancún, Quintana Roo</p>
                <p style="margin: 0">Tel: 923 235 1538</p>
                <p style="margin: 0">Email: perezaugusto210@gmail.com</p>
            </td>

        </tr>
    </table>

</div>
</footer>

    <main>
    <div class="wrapper">
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
                                @endphp
                                <tr class="{{ $isOutOfRange ? 'out-of-range' : '' }}">
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
                        </tbody>

                    </table>

                    <div class="study-details">
                        
                        <table width="100%" style="margin-top: 10px; font-size: 10px;">
                            <tr>
                                <!-- Columna 1: Elaboró y Validó -->
                                <td style="vertical-align: top; width: 50%; padding-right: 10px;">
                                    <div style="margin-bottom: 5px;">
                                        <span class="info-label">Elaboró:</span> {{ $estudio->elaboro }}
                                    </div>
                                    <div>
                                        <span class="info-label">Validó:</span> {{ $estudio->valido }}
                                    </div>
                                </td>

                                <!-- Columna 2: Tipo de muestra y Método -->
                                <td style="vertical-align: top; width: 50%; padding-left: 10px;">
                                    <div style="margin-bottom: 5px; margin-top: 2px;">
                                        <span class="info-label">Tipo de muestra:</span> {{ strtoupper($estudio->tipo_muestra) }}
                                    </div>
                                    <div>
                                        <span class="info-label">Método:</span> {{ strtoupper($estudio->metodo) }}
                                    </div>
                                </td>
                            </tr>

                        </table>
                        
                        
                    </div>
                </div>
            @endforeach
    </div>
</main>
        {{-- <div style="height: 10px;"></div> <!-- Espacio para el footer --> --}}


         <div style="
                position: fixed;
                bottom: 120px; /* Ajusta según la altura de tu footer */
                left: 0;
                width: 100%;
                text-align: center;
                opacity: 0.3;
                z-index: -100;
            ">
                <img src="{{ public_path('img/imgbiolabfoot.png') }}" style="width: 600px;" alt="Biolab">
            </div>

   




</body>


</html>