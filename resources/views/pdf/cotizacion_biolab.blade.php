<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 1cm 1.5cm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #333;
        }

        .header {
            margin-bottom: 20px;
            border-bottom: 3px solid #08cc71;
            padding-bottom: 15px;
        }

        .header-content {
            display: table;
            width: 100%;
        }

        .header-left {
            display: table-cell;
            width: 60%;
            vertical-align: middle;
        }

        .header-right {
            display: table-cell;
            width: 40%;
            vertical-align: middle;
            text-align: right;
        }

        .logo-section {
            margin-bottom: 10px;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #08cc71;
            margin-bottom: 5px;
        }

        .company-info {
            font-size: 9px;
            color: #666;
            line-height: 1.5;
        }

        .document-title {
            font-size: 24px;
            font-weight: bold;
            color: #08cc71;
            margin: 0;
        }

        .document-subtitle {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
        }

        .info-box {
            background-color: #f8fdf9;
            border: 1px solid #08cc71;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .info-row {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }

        .info-row:last-child {
            margin-bottom: 0;
        }

        .info-label {
            display: table-cell;
            font-weight: bold;
            width: 150px;
            color: #555;
        }

        .info-value {
            display: table-cell;
            color: #333;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #08cc71;
            margin: 20px 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 2px solid #e0f2e9;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .table th {
            background-color: #08cc71;
            color: white;
            font-weight: bold;
            padding: 10px 8px;
            text-align: left;
            font-size: 11px;
        }

        .table td {
            padding: 8px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 10px;
        }

        .table tbody tr:nth-child(odd) {
            background-color: #ffffff;
        }

        .table tbody tr:nth-child(even) {
            background-color: #f8fdf9;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .totals-section {
            margin-top: 20px;
            float: right;
            width: 300px;
        }

        .totals-row {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }

        .totals-label {
            display: table-cell;
            text-align: right;
            padding-right: 15px;
            font-size: 12px;
            color: #555;
        }

        .totals-value {
            display: table-cell;
            text-align: right;
            font-weight: bold;
            font-size: 12px;
            width: 120px;
        }

        .total-final {
            border-top: 2px solid #08cc71;
            padding-top: 10px;
            margin-top: 10px;
        }

        .total-final .totals-label {
            font-size: 14px;
            font-weight: bold;
            color: #08cc71;
        }

        .total-final .totals-value {
            font-size: 16px;
            color: #08cc71;
        }

        .notes-section {
            clear: both;
            background-color: #f9f9f9;
            border-left: 4px solid #08cc71;
            padding: 15px;
            margin-top: 30px;
            margin-bottom: 20px;
        }

        .notes-title {
            font-weight: bold;
            color: #08cc71;
            margin-bottom: 8px;
        }

        .notes-content {
            font-size: 10px;
            line-height: 1.6;
            color: #555;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 80px;
            border-top: 2px solid #08cc71;
            padding-top: 10px;
            text-align: center;
        }

        .footer-content {
            display: table;
            width: 100%;
        }

        .footer-section {
            display: table-cell;
            width: 33.33%;
            vertical-align: top;
            font-size: 9px;
            color: #666;
            padding: 0 10px;
        }

        .footer-section strong {
            color: #08cc71;
            font-size: 10px;
        }

        .footer-divider {
            border-left: 1px solid #ddd;
        }

        .watermark {
            position: fixed;
            top: 35%;
            left: 0;
            width: 100%;
            text-align: center;
            opacity: 0.05;
            font-size: 80px;
            font-weight: bold;
            color: #08cc71;
            transform: rotate(-45deg);
            z-index: -1;
        }

        .validity-notice {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 10px;
            color: #856404;
        }

        .item-description {
            font-size: 9px;
            color: #777;
            font-style: italic;
            margin-top: 3px;
        }

        .examenes-list {
            margin-top: 5px;
            padding-left: 15px;
            font-size: 9px;
            color: #555;
        }

        .examenes-list li {
            margin-bottom: 2px;
            list-style-type: none;
            position: relative;
            padding-left: 12px;
        }

        .examenes-list li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #08cc71;
            font-weight: bold;
        }

        .examenes-title {
            font-size: 9px;
            font-weight: bold;
            color: #08cc71;
            margin-top: 5px;
            margin-bottom: 3px;
        }
    </style>
</head>
<body>
    <!-- Marca de agua -->
    <div class="watermark">COTIZACIÓN</div>

    <!-- Encabezado -->
    <div class="header">
        <div class="header-content">
            <div class="header-left">
                <div class="logo-section">
                    <img src="{{ public_path('img/imgbiolabtrans.png') }}" style="width: 80px; margin-bottom: 10px;">
                </div>
                <div class="company-name">
                    Laboratorio de Análisis Clínicos "BIOLAB"
                </div>
                <div class="company-info">
                    Avenida Revolución, Región 75, Calle 37 Norte<br>
                    C.P. 77527, Cancún, Quintana Roo<br>
                    Tel: 923 235 1538 | biolab348@gmail.com
                </div>
            </div>
            <div class="header-right">
                <div class="document-title">COTIZACIÓN</div>
                <div class="document-subtitle">
                    Fecha: {{ \Carbon\Carbon::parse($fecha_cotizacion)->format('d/m/Y') }}
                </div>
            </div>
        </div>
    </div>

    <!-- Aviso de vigencia -->
    @if($vigencia)
    <div class="validity-notice">
        <strong>⚠ VIGENCIA:</strong> Esta cotización tiene una validez de {{ $vigencia }} días a partir de la fecha de emisión.
    </div>
    @endif

    <!-- Información del Cliente -->
    <div class="info-box">
        <div class="info-row">
            <div class="info-label">Cliente:</div>
            <div class="info-value"><strong>{{ strtoupper($cliente['nombre']) }}</strong></div>
        </div>
        @if(!empty($cliente['email']))
        <div class="info-row">
            <div class="info-label">Email:</div>
            <div class="info-value">{{ $cliente['email'] }}</div>
        </div>
        @endif
        @if(!empty($cliente['telefono']))
        <div class="info-row">
            <div class="info-label">Teléfono:</div>
            <div class="info-value">{{ $cliente['telefono'] }}</div>
        </div>
        @endif
        @if(!empty($cliente['direccion']))
        <div class="info-row">
            <div class="info-label">Dirección:</div>
            <div class="info-value">{{ $cliente['direccion'] }}</div>
        </div>
        @endif
    </div>

    <!-- Tabla de Estudios -->
    <div class="section-title">Estudios Cotizados</div>
    
    <table class="table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 50%;">Descripción</th>
                <th style="width: 15%;" class="text-center">Cantidad</th>
                <th style="width: 15%;" class="text-right">Precio Unitario</th>
                <th style="width: 15%;" class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($estudios as $index => $estudio)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>
                    <strong>{{ strtoupper($estudio['nombre']) }}</strong>
                    @if(!empty($estudio['descripcion']))
                    <div class="item-description">{{ $estudio['descripcion'] }}</div>
                    @endif
                    @if(!empty($estudio['examenes']) && is_array($estudio['examenes']) && count($estudio['examenes']) > 0)
                    <div class="examenes-title">Incluye:</div>
                    <ul class="examenes-list">
                        @foreach($estudio['examenes'] as $examen)
                        <li>{{ $examen['nombre_examen'] }}</li>
                        @endforeach
                    </ul>
                    @endif
                </td>
                <td class="text-center">{{ $estudio['cantidad'] }}</td>
                <td class="text-right">${{ number_format($estudio['precio'], 2) }}</td>
                <td class="text-right">${{ number_format($estudio['precio'] * $estudio['cantidad'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totales -->
    <div class="totals-section">
        <div class="totals-row">
            <div class="totals-label">Subtotal:</div>
            <div class="totals-value">${{ number_format($subtotal, 2) }}</div>
        </div>
        @if($descuento > 0)
        <div class="totals-row">
            <div class="totals-label">Descuento ({{ $descuento }}%):</div>
            <div class="totals-value" style="color: #dc3545;">-${{ number_format($monto_descuento, 2) }}</div>
        </div>
        @endif
        <div class="totals-row total-final">
            <div class="totals-label">TOTAL:</div>
            <div class="totals-value">${{ number_format($total, 2) }} MXN</div>
        </div>
    </div>

    <!-- Notas adicionales -->
    @if(!empty($notas))
    <div class="notes-section">
        <div class="notes-title">Notas y Condiciones:</div>
        <div class="notes-content">{{ $notas }}</div>
    </div>
    @endif

    <!-- Condiciones generales -->
    <div style="clear: both; margin-top: 40px; font-size: 9px; color: #777; line-height: 1.6;">
        <p style="margin: 5px 0;"><strong>Condiciones generales:</strong></p>
        <ul style="margin: 5px 0; padding-left: 20px;">
            <li>Los precios están expresados en pesos mexicanos (MXN)</li>
            <li>Esta cotización no incluye IVA</li>
            <li>Los estudios deben ser realizados en las instalaciones del laboratorio</li>
            <li>Es necesario presentar identificación oficial al momento de realizar los estudios</li>
            <li>Los resultados serán entregados según los tiempos establecidos para cada estudio</li>
        </ul>
    </div>

    <!-- Pie de página -->
    <div class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <strong>Contacto</strong><br>
                Tel: 923 235 1538<br>
                biolab348@gmail.com
            </div>
            <div class="footer-section footer-divider">
                <strong>Responsable Técnico</strong><br>
                Q.F.B Ángel Augusto Pérez Arias<br>
                Céd. Prof. 14083392
            </div>
            <div class="footer-section footer-divider">
                <strong>Dirección</strong><br>
                Av. Revolución, Región 75<br>
                Cancún, Q. Roo, C.P. 77527
            </div>
        </div>
        <div style="margin-top: 10px; font-size: 8px; color: #999; font-style: italic;">
            "Ciencia y compromiso al servicio de tu salud"
        </div>
    </div>
</body>
</html>
