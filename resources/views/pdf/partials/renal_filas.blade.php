{{--
    T10: renglones calculados (TFGe / RAC) debajo de su analito, dentro de la tabla
    del estudio. $filas viene de App\Support\FuncionRenal::analizar().
--}}
@foreach ($filas as $fila)
    @php
        $fuera = $fila['bajo'] || $fila['alto'];
        $categorias = $fila['tabla'] === 'A' ? \App\Support\FuncionRenal::CATEGORIAS_A : \App\Support\FuncionRenal::CATEGORIAS_G;
    @endphp
    <tr>
        <td class="exam-name {{ $fuera ? 'fuera-rango' : '' }}">{{ $fila['nombre'] }}</td>
        <td class="result-value {{ $fuera ? 'fuera-rango' : '' }}">
            {{ $fila['texto'] }}@if($fila['bajo'])<span class="flecha-rango">↓</span>@elseif($fila['alto'])<span class="flecha-rango">↑</span>@endif
        </td>
        <td class="unit">{{ $fila['unidad'] }}</td>
        <td class="reference">{{ $fila['referencia'] }}</td>
    </tr>
    <tr class="fila-niveles">
        <td colspan="4">
            <table class="tabla-niveles">
                <tr>
                    <th>{{ $fila['tabla'] === 'A' ? 'RAC (mg/g)' : 'TFGe (mL/min/1.73 m²)' }}</th>
                    <th>Categoría</th>
                </tr>
                @foreach ($categorias as $c)
                    @php $esActual = ($fila['categoria']['codigo'] ?? null) === $c['codigo']; @endphp
                    <tr class="{{ $esActual ? 'nivel-actual' : '' }}">
                        <td>{{ $c['rango'] }}</td>
                        <td>{{ $c['codigo'] }} {{ $c['nombre'] }}@if($esActual) &nbsp;◄ Resultado @endif</td>
                    </tr>
                @endforeach
            </table>
            <div style="font-size: 8px; color: #6b7280; margin: 2px 0 0 12px;">Calculado: {{ $fila['formula'] }}</div>
            @if($fila['tabla'] === 'G')
                {{-- Nota fija debajo de la TFGe --}}
                <div style="font-size: 8.5px; color: #374151; line-height: 1.3; margin: 3px 0 2px 12px;">{{ \App\Support\FuncionRenal::NOTA_TFGE }}</div>
            @endif
        </td>
    </tr>
@endforeach
