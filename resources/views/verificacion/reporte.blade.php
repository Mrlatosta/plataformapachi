@php
    $formatoFecha = fn ($fecha) => $fecha
        ? \Carbon\Carbon::parse($fecha)->locale('es')->translatedFormat('d \d\e F \d\e Y')
        : '—';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $reporte ? "Reporte {$reporte->folio} verificado" : 'Documento no encontrado' }} · Biolab</title>
    <style>
        :root { --verde: #15803d; --rojo: #b91c1c; --texto: #1f2937; --suave: #6b7280; --borde: #e5e7eb; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; background: #f3f4f6; color: var(--texto); }
        .contenedor { max-width: 480px; margin: 0 auto; padding: 24px 16px 40px; }
        .logo { display: block; margin: 0 auto 16px; width: 96px; }
        .tarjeta { background: #fff; border-radius: 16px; box-shadow: 0 1px 3px rgba(0, 0, 0, .08); overflow: hidden; }
        .estado { padding: 20px; text-align: center; color: #fff; }
        .estado.valido { background: var(--verde); }
        .estado.invalido { background: var(--rojo); }
        .estado .icono { font-size: 40px; line-height: 1; }
        .estado h1 { margin: 8px 0 4px; font-size: 20px; }
        .estado p { margin: 0; opacity: .9; font-size: 14px; }
        .datos { padding: 8px 20px; }
        .fila { display: flex; justify-content: space-between; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--borde); font-size: 14px; }
        .fila:last-child { border-bottom: 0; }
        .fila span:first-child { color: var(--suave); }
        .fila span:last-child { font-weight: 600; text-align: right; }
        .estudios { padding: 4px 20px 16px; }
        .estudios h2 { font-size: 12px; text-transform: uppercase; letter-spacing: .05em; color: var(--suave); margin: 8px 0; }
        .estudios ul { margin: 0; padding-left: 18px; font-size: 14px; }
        .estudios li { padding: 3px 0; }
        .acciones { padding: 0 20px 20px; }
        .boton { display: block; text-align: center; background: #111827; color: #fff; text-decoration: none; padding: 14px; border-radius: 10px; font-weight: 600; }
        .mensaje { padding: 20px; font-size: 14px; line-height: 1.6; }
        .nota { font-size: 12px; color: var(--suave); text-align: center; margin: 16px 8px 0; line-height: 1.5; }
        .contacto { font-size: 12px; color: var(--suave); text-align: center; margin-top: 20px; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="contenedor">
        <img class="logo" src="{{ asset('img/imgbiolabtrans.png') }}" alt="Biolab">

        <div class="tarjeta">
            @if ($reporte)
                <div class="estado valido">
                    <div class="icono">✔</div>
                    <h1>Documento auténtico</h1>
                    <p>Este reporte fue emitido por Biolab</p>
                </div>

                <div class="datos">
                    <div class="fila"><span>Folio</span><span>{{ $reporte->folio }}</span></div>
                    <div class="fila"><span>Paciente</span><span>{{ $paciente }}</span></div>
                    <div class="fila"><span>Toma de muestra</span><span>{{ $formatoFecha($reporte->toma_muestra) }}</span></div>
                    <div class="fila"><span>Validación</span><span>{{ $formatoFecha($reporte->fecha_validacion) }}</span></div>
                </div>

                @if ($reporte->estudios->isNotEmpty())
                    <div class="estudios">
                        <h2>Estudios incluidos</h2>
                        <ul>
                            @foreach ($reporte->estudios as $reporteEstudio)
                                <li>{{ $reporteEstudio->estudio->nombre ?? 'Estudio' }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="acciones">
                    <a class="boton" href="{{ route('reportes.verificar.pdf', $token) }}" target="_blank" rel="noopener">
                        Ver reporte completo (PDF)
                    </a>
                </div>
            @else
                <div class="estado invalido">
                    <div class="icono">✖</div>
                    <h1>Documento no encontrado</h1>
                    <p>No pudimos verificar este código</p>
                </div>

                <div class="mensaje">
                    El código escaneado no corresponde a ningún reporte emitido por Biolab.
                    Si recibiste este documento, comunícate con el laboratorio para confirmar su autenticidad.
                </div>
            @endif
        </div>

        @if ($reporte)
            <p class="nota">
                Los resultados deben ser interpretados por su médico tratante.
                No compartas este código: da acceso a tu reporte.
            </p>
        @endif

        <div class="contacto">
            <strong>Biolab</strong> · "Ciencia y compromiso al servicio de tu salud"<br>
            Q.F.B. Ángel Augusto Pérez Arias · Céd. Prof. 14083392<br>
            WhatsApp 998 654 3958
        </div>
    </div>
</body>
</html>
