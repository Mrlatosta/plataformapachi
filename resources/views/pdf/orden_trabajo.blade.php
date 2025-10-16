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

      header {
        position: fixed;
        top: 0cm;
        left: 0cm;
        right: 0cm;
        height: 6cm;
      }

      footer {
        position: fixed;
        bottom: 0cm;
        left: 0cm;
        right: 0cm;
        height: 6cm;
      }

      main {
        margin-top: 6.5cm;
        margin-bottom: 6.5cm;
        padding: 0 0.5cm;
      }

      .tablaEstudios {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
        margin-top: 10px;
      }

      .tablaEstudios th,
      .tablaEstudios td {
        padding: 6px;
        text-align: left;
        border-bottom: 1px solid #ccc;
      }

      .tablaEstudios th {
        background-color: #f2f2f2;
        font-weight: bold;
      }

      .total {
        text-align: right;
        font-size: 14px;
        font-weight: bold;
        margin-top: 10px;
      }

      body::before {
        content: "";
        position: fixed;
        top: 35%;
        left: 0;
        width: 100%;
        height: 100%;
        background: url("{{ public_path('img/imgbiolabfoot.png') }}") no-repeat center center;
        background-size: 550px;
        opacity: 0.13;
        z-index: -100;
      }
    </style>
  </head>
  <body>
    <!-- HEADER -->
    <header>
      <table width="100%" style="margin-top: 10px; font-size: 10px; border: none">
        <tr>
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
                    <div>Folio de Orden: {{ $reporte->folio }}</div>
                    <div><img src="{{ public_path('img/codbarras.png') }}" style="width: 80px; height: 20px;" alt=""></div>
                    <div style="margin-top: 5px;">
                      Médico solicitante:
                      <strong>{{ strtoupper($reporte->medico_solicitante ?: 'A QUIEN CORRESPONDA') }}</strong>
                    </div>
                  </div>
                </td>
              </tr>
            </table>
          </td>

          <td width="30%" valign="top">
            <div style="background-color: #e9f0fe; padding: 10px 15px; font-size: 10px; border: 1px solid #cbd5e0; border-radius: 5px;">
              <div><strong>Fecha:</strong><br>{{ \Carbon\Carbon::parse($reporte->fecha_reporte)->format('d/m/Y g:i a') }}</div>
              <div style="margin-top: 5px;"><strong>ID:</strong><br>{{ $reporte->id }}</div>
            </div>
          </td>
        </tr>
      </table>

      <div class="patient-info" style="margin-top: 10px;">
        <table width="100%" style="font-size: 12px;">
          <tr>
            <td style="vertical-align: top; width: 50%; padding-right: 10px;">
              <div style="margin-bottom: 5px;">
                <strong>Paciente:</strong> {{ strtoupper($reporte->nombre_cliente) }}
              </div>
              <div style="margin-bottom: 5px;">
                <strong>F. Nacimiento:</strong> {{ \Carbon\Carbon::parse($reporte->fecha_nacimiento)->format('d-m-Y') }}
              </div>
              <div><strong>Sexo:</strong> {{ ucfirst($reporte->sexo) }}</div>
            </td>

            <td style="vertical-align: top; width: 50%; padding-left: 10px;">
              <div style="margin-bottom: 5px;">
                <strong>Correo:</strong> {{ strtoupper($reporte->email ?: 'NO ESPECIFICADO') }}
              </div>
              <div><strong>Edad:</strong> {{ $reporte->edad }} años</div>
            </td>
          </tr>
        </table>
      </div>

      <hr style="width: 100%; border: 1px solid #000; margin: 5px 0;">
    </header>

    <!-- FOOTER -->
    <footer>
      <p style="margin-left: 45px; font-size: 0.6rem; color: gray; font-style: italic;">"Ciencia y compromiso al servicio de tu salud".</p>
      <table width="100%" style="font-size: 9px;">
        <tr>
          <td width="25%" align="center">
            <img src="{{ public_path('img/codigoqr.png') }}" width="80px" alt="QR">
          </td>
          <td width="25%">
            <img src="{{ public_path('img/firmados.png') }}" width="100px">
            <hr>
            <strong>Q.F.B Ángel Augusto Pérez Arias</strong><br>
            Univ. Popular de la Chontalpa<br>
            Céd. Prof. 14083392<br>
            <img src="{{ public_path('img/whatsapp.png') }}" width="12px"> 923 235 1538
          </td>
          <td width="25%" align="center">
            <img src="{{ public_path('img/imgbiolabtrans.png') }}" width="80px">
          </td>
          <td width="25%">
            Av. Revolución, Región 75, Calle 37 Norte<br>
            C.P. 77527, Cancún, Quintana Roo<br>
            Tel: 923 235 1538<br>
            <strong>biolab348@gmail.com</strong>
          </td>
        </tr>
      </table>
    </footer>

    <!-- MAIN -->
    <main>
      <h3 style="text-align: center; text-transform: uppercase;">Orden de Trabajo</h3>

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

      <p style="margin-top: 20px; font-size: 10px; text-align: justify;">
        El paciente se compromete a cumplir con las condiciones requeridas por el laboratorio para su estudio.
        Los estudios están sujetos a variaciones por factores como alimentación, horario, ejercicio o medicamentos.
        Para una interpretación adecuada, los resultados deben ser evaluados por su médico tratante.
      </p>
    </main>
  </body>
</html>
