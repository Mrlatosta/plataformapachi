<html>
<head>
    <style>
        @page { margin: 1.2cm 1cm 1.4cm 1cm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111827; }
        .encabezado { border-bottom: 2px solid #08cc71; padding-bottom: 6px; margin-bottom: 8px; }
        .encabezado td { vertical-align: middle; }
        h1 { font-size: 14px; margin: 0; }
        .sub { font-size: 10px; color: #374151; margin-top: 2px; }
        table.datos { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.datos thead { display: table-header-group; }
        table.datos th { background: #08cc71; color: #fff; text-align: left; padding: 3px 4px; font-size: 9px; }
        table.datos td { padding: 3px 4px; border-bottom: 0.5px solid #e5e7eb; vertical-align: top; }
        table.datos tr { page-break-inside: avoid; }
        .num { text-align: right; white-space: nowrap; }
        .medico td { background: #ecfdf5; font-weight: bold; font-size: 10px; border-bottom: 1px solid #08cc71; }
        .subtotal td { font-weight: bold; border-top: 1px solid #9ca3af; }
        .pagada { color: #047857; }
        .pendiente { color: #b45309; }
        .total { width: 100%; border-collapse: collapse; margin-top: 6px; font-size: 11px; page-break-inside: avoid; }
        .total td { padding: 5px 6px; border: 1.5px solid #08cc71; font-weight: bold; }
        .nota { font-size: 8px; color: #6b7280; margin-top: 6px; }
    </style>
</head>
<body>
    <table width="100%" class="encabezado">
        <tr>
            <td style="width: 70px;"><img src="{{ public_path('img/pdf/imgbiolabtrans.png') }}" style="width: 60px;"></td>
            <td>
                <h1>Laboratorio de Análisis Clínicos "BIOLAB"</h1>
                <div class="sub">{{ $titulo }}</div>
                <div class="sub">Generado el {{ now(config('app.local_timezone'))->format('d/m/Y g:i a') }}</div>
            </td>
        </tr>
    </table>

    @if ($filas->isEmpty())
        <p>No hay comisiones en este periodo.</p>
    @else
        <table class="datos">
            <thead>
                <tr>
                    <th style="width: 9%;">Folio</th>
                    <th style="width: 9%;">Fecha</th>
                    <th style="width: 20%;">Paciente</th>
                    <th>Estudios</th>
                    <th class="num" style="width: 10%;">Monto cobrado</th>
                    <th class="num" style="width: 5%;">%</th>
                    <th class="num" style="width: 10%;">Comisión</th>
                    <th style="width: 12%;">Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($por_medico as $medico)
                    <tr class="medico"><td colspan="8">{{ $medico['medico'] }}</td></tr>
                    @foreach ($filas->where('medico_id', $medico['medico_id']) as $fila)
                        <tr>
                            <td>{{ $fila['folio'] }}</td>
                            <td>{{ \Carbon\Carbon::parse($fila['fecha'])->format('d/m/Y') }}</td>
                            <td>{{ $fila['paciente'] }}</td>
                            <td>{{ $fila['estudios'] }}</td>
                            <td class="num">${{ number_format($fila['monto'], 2) }}</td>
                            <td class="num">{{ rtrim(rtrim(number_format($fila['porcentaje'], 2), '0'), '.') }}%</td>
                            <td class="num">${{ number_format($fila['comision'], 2) }}</td>
                            <td class="{{ $fila['estado'] }}">
                                {{ $fila['estado'] === 'pagada' ? 'Pagada '.\Carbon\Carbon::parse($fila['pagada_at'])->format('d/m/Y') : 'Pendiente' }}
                            </td>
                        </tr>
                    @endforeach
                    <tr class="subtotal">
                        <td colspan="4">Total {{ $medico['medico'] }} · {{ $medico['ordenes'] }} {{ $medico['ordenes'] === 1 ? 'orden' : 'órdenes' }}</td>
                        <td class="num">${{ number_format($medico['monto'], 2) }}</td>
                        <td></td>
                        <td class="num">${{ number_format($medico['comision'], 2) }}</td>
                        <td class="pendiente">@if($medico['pendiente'] > 0) Pendiente ${{ number_format($medico['pendiente'], 2) }} @endif</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="total">
            <tr>
                <td>TOTAL GENERAL · {{ $total['ordenes'] }} {{ $total['ordenes'] === 1 ? 'orden' : 'órdenes' }}</td>
                <td class="num">Cobrado: ${{ number_format($total['monto'], 2) }}</td>
                <td class="num">Comisiones: ${{ number_format($total['comision'], 2) }}</td>
                <td class="num">Pagado: ${{ number_format($total['pagada'], 2) }}</td>
                <td class="num">Pendiente: ${{ number_format($total['pendiente'], 2) }}</td>
            </tr>
        </table>
    @endif

    <p class="nota">
        Comisión = monto cobrado × % del médico. Monto cobrado: {{ $base === 'con_iva' ? 'total con IVA' : 'subtotal después de descuento, sin IVA' }}.
        Las órdenes sin médico («A quien corresponda») no generan comisión.
    </p>
</body>
</html>
