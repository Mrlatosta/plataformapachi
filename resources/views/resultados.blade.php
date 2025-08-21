<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Resultados</title>
    <style>
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid black; padding: 6px; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Resultados de Estudios</h1>
    @foreach ($estudios as $estudio)
        <h3>{{ $estudio->nombre }}</h3>
        <table>
            <thead>
                <tr>
                    <th>Examen</th>
                    <th>Resultado</th>
                    <th>Unidad</th>
                    <th>Referencia</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($estudio->examenes as $examen)
                    <tr>
                        <td>{{ $examen->nombre_examen }}</td>
                        <td>{{ $examen->resultado->resultado ?? '' }}</td>
                        <td>{{ $examen->unidad }}</td>
                        <td>{{ $examen->valor_referencia }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach
</body>
</html>
