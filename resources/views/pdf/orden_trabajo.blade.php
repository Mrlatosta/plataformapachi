<html>
  <head>
    <style>
      @page {
        margin: 0;
      }

      body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 11px;
        margin: 0;
        padding: 0;
      }

      .orden-container {
        page-break-after: avoid;
        position: relative;
        height: 50vh;
        padding: 0.3cm;
        box-sizing: border-box;
      }

      .orden-container:first-child {
        border-bottom: 2px dashed #999;
      }

      .orden-container::before {
        content: "";
        position: absolute;
        top: 35%;
        left: 0;
        width: 100%;
        height: 100%;
        background: url("{{ public_path('img/imgbiolabfoot.png') }}") no-repeat center center;
        background-size: 350px;
        opacity: 0.13;
        z-index: -1;
      }

      .header-section {
        margin-bottom: 8px;
      }

      .marca-agua {
        position: absolute;
        top: 10px;
        right: 10px;
        font-size: 9px;
        color: #666;
        font-weight: bold;
        text-transform: uppercase;
      }

      .tablaEstudios {
        width: 100%;
        border-collapse: collapse;
        font-size: 10px;
        margin-top: 8px;
      }

      .tablaEstudios th,
      .tablaEstudios td {
        padding: 4px;
        text-align: left;
        border-bottom: 1px solid #ccc;
      }

      .tablaEstudios th {
        background-color: #f2f2f2;
        font-weight: bold;
      }

      .total {
        text-align: right;
        font-size: 12px;
        font-weight: bold;
        margin-top: 8px;
      }

      .footer-section {
        margin-top: 10px;
        font-size: 8px;
      }

      .footer-section table {
        width: 100%;
      }

      .footer-section td {
        vertical-align: top;
        padding: 2px;
      }
    </style>
  </head>
  <body>
    @for ($copia = 1; $copia <= 2; $copia++)
    <div class="orden-container">
      <div class="marca-agua">{{ $copia == 1 ? 'COPIA CLIENTE' : 'COPIA LABORATORIO' }}</div>
      
      <!-- HEADER -->
      <div class="header-section">
        <table width="100%" style="font-size: 9px; border: none">
          <tr>
            <td width="70%" valign="top">
              <table>
                <tr>
                  <td>
                    <img src="{{ public_path('img/imgbiolabtrans.png') }}" style="width: 70px; margin-right: 5px;">
                  </td>
                  <td style="padding-left: 8px;">
                    <div style="font-weight: bold; font-size: 12px;">
                      Laboratorio de Análisis Clínicos "BIOLAB"
                    </div>
                    <div style="font-size: 8px;">
                      <div>Folio: {{ $reporte->folio }}</div>
                      <div><img src="{{ public_path('img/codbarras.png') }}" style="width: 60px; height: 15px;" alt=""></div>
                      <div style="margin-top: 3px;">
                        Médico: <strong>{{ strtoupper($reporte->medico_solicitante ?: 'A QUIEN CORRESPONDA') }}</strong>
                      </div>
                    </div>
                  </td>
                </tr>
              </table>
            </td>

            <td width="30%" valign="top">
              <div style="background-color: #e9f0fe; padding: 6px 10px; font-size: 8px; border: 1px solid #cbd5e0; border-radius: 3px;">
                <div><strong>Fecha:</strong><br>{{ \Carbon\Carbon::parse($reporte->fecha_reporte)->format('d/m/Y g:i a') }}</div>
                <div style="margin-top: 3px;"><strong>ID:</strong> {{ $reporte->id }}</div>
              </div>
            </td>
          </tr>
        </table>

        <div class="patient-info" style="margin-top: 6px;">
          <table width="100%" style="font-size: 9px;">
            <tr>
              <td style="vertical-align: top; width: 50%; padding-right: 8px;">
                <div style="margin-bottom: 3px;">
                  <strong>Paciente:</strong> {{ strtoupper($reporte->nombre_cliente) }}
                </div>
                <div style="margin-bottom: 3px;">
                  <strong>F. Nacimiento:</strong> {{ \Carbon\Carbon::parse($reporte->fecha_nacimiento)->format('d-m-Y') }}
                </div>
                <div><strong>Sexo:</strong> {{ ucfirst($reporte->sexo) }}</div>
              </td>

              <td style="vertical-align: top; width: 50%; padding-left: 8px;">
                <div style="margin-bottom: 3px;">
                  <strong>Correo:</strong> {{ strtoupper($reporte->email ?: 'NO ESPECIFICADO') }}
                </div>
                <div><strong>Edad:</strong> {{ $reporte->edad }} años</div>
              </td>
            </tr>
          </table>
        </div>

        <hr style="width: 100%; border: 1px solid #000; margin: 3px 0;">
      </div>

      <!-- MAIN -->
      <div class="main-section">
        <h3 style="text-align: center; text-transform: uppercase; font-size: 11px; margin: 5px 0;">Orden de Trabajo</h3>

        <table class="tablaEstudios">
          <thead>
            <tr>
              <th>Estudio</th>
              <th style="text-align:right;">Precio ($)</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($reporte->estudios as $estudio)
            <tr>
              <td>{{ strtoupper($estudio->estudio->nombre) }}</td>
              <td style="text-align:right;">${{ number_format($estudio->precio, 2) }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>

        <div class="total">
          Total a Pagar: ${{ number_format($reporte->estudios->sum('precio'), 2) }}
        </div>

        <p style="margin-top: 8px; font-size: 7px; text-align: justify;">
          El paciente se compromete a cumplir con las condiciones requeridas por el laboratorio para su estudio.
          Los estudios están sujetos a variaciones por factores como alimentación, horario, ejercicio o medicamentos.
          Para una interpretación adecuada, los resultados deben ser evaluados por su médico tratante.
        </p>
      </div>

      <!-- FOOTER -->
      <div class="footer-section">
        <p style="margin-left: 10px; font-size: 6px; color: gray; font-style: italic;">"Ciencia y compromiso al servicio de tu salud".</p>
        <table>
          <tr>
            <td width="25%" align="center">
              <img src="{{ public_path('img/codigoqr.png') }}" width="50px" alt="QR">
            </td>
            <td width="25%">
              <img src="{{ public_path('img/firmados.png') }}" width="60px">
              <hr style="margin: 2px 0;">
              <strong style="font-size: 7px;">Q.F.B Ángel Augusto Pérez Arias</strong><br>
              <span style="font-size: 6px;">Univ. Popular de la Chontalpa</span><br>
              <span style="font-size: 6px;">Céd. Prof. 14083392</span><br>
              <img src="{{ public_path('img/whatsapp.png') }}" width="8px"> <span style="font-size: 6px;">923 235 1538</span>
            </td>
            <td width="25%" align="center">
              <img src="{{ public_path('img/imgbiolabtrans.png') }}" width="50px">
            </td>
            <td width="25%" style="font-size: 7px;">
              Av. Revolución, Región 75, Calle 37 Norte<br>
              C.P. 77527, Cancún, Quintana Roo<br>
              Tel: 923 235 1538<br>
              <strong>biolab348@gmail.com</strong>
            </td>
          </tr>
        </table>
      </div>
    </div>
    @endfor
  </body>
</html>