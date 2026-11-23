{{--
    T10: renglones calculados dentro de la tabla del estudio. $filas viene de
    App\Support\FuncionRenal::analizar().
     - TFGe: renglon + tabla G1–G5 + nota fija.
     - ACR (relacion albumina/creatinina): como lo pidio el laboratorio, con
       "Calculada: ..." bajo el nombre y la franja A1 / A2 / A3 a todo el ancho.
--}}
@foreach ($filas as $fila)
    @php $fuera = $fila['bajo'] || $fila['alto']; @endphp
    <tr>
        <td class="exam-name {{ $fuera ? 'fuera-rango' : '' }}">
            {{ $fila['nombre'] }}
            @if($fila['tabla'] === 'A')
                <div style="font-size: 8.5px; font-weight: normal; color: #4b5563;">Calculada: {{ $fila['formula'] }}</div>
            @endif
        </td>
        <td class="result-value {{ $fuera ? 'fuera-rango' : '' }}">
            {{ $fila['texto'] }}@if($fila['bajo'])<span class="flecha-rango">↓</span>@elseif($fila['alto'])<span class="flecha-rango">↑</span>@endif
        </td>
        <td class="unit">{{ $fila['unidad'] }}</td>
        <td class="reference">{{ $fila['referencia'] }}</td>
    </tr>

    @if($fila['tabla'] === 'A')
        <tr class="fila-niveles">
            <td colspan="4">
                <table class="pr-franja" style="margin: 2px 0 4px 0;">
                    <tr>
                        @foreach (\App\Support\FuncionRenal::CATEGORIAS_A as $c)
                            <td class="{{ ($fila['categoria']['codigo'] ?? null) === $c['codigo'] ? 'pr-actual' : '' }}">
                                <strong>{{ $c['codigo'] }}</strong> · {{ $c['rango'] }} mg/g · {{ $c['nombre'] }}
                            </td>
                        @endforeach
                    </tr>
                </table>
            </td>
        </tr>
    @else
        <tr class="fila-niveles">
            <td colspan="4">
                <table class="tabla-niveles">
                    <tr>
                        <th>TFGe (mL/min/1.73 m²)</th>
                        <th>Categoría</th>
                    </tr>
                    @foreach (\App\Support\FuncionRenal::CATEGORIAS_G as $c)
                        @php $esActual = ($fila['categoria']['codigo'] ?? null) === $c['codigo']; @endphp
                        <tr class="{{ $esActual ? 'nivel-actual' : '' }}">
                            <td>{{ $c['rango'] }}</td>
                            <td>{{ $c['codigo'] }} {{ $c['nombre'] }}@if($esActual) &nbsp;◄ Resultado @endif</td>
                        </tr>
                    @endforeach
                </table>
                <div style="font-size: 8px; color: #6b7280; margin: 2px 0 0 12px;">Calculado: {{ $fila['formula'] }}</div>
                {{-- Nota fija debajo de la TFGe --}}
                <div style="font-size: 8.5px; color: #374151; line-height: 1.3; margin: 3px 0 2px 12px;">{{ \App\Support\FuncionRenal::NOTA_TFGE }}</div>
            </td>
        </tr>
    @endif
@endforeach
