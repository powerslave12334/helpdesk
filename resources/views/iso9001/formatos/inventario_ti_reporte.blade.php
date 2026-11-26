<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $formato['clave'] }} - {{ $formato['nombre'] }} - Reporte</title>
    <style>
        @font-face {
            font-family: 'Carlito';
            font-style: normal;
            font-weight: normal;
            src: url('{{ str_replace('\\', '/', public_path('fonts/Carlito-Regular.ttf')) }}') format('truetype');
        }
        @font-face {
            font-family: 'Carlito';
            font-style: normal;
            font-weight: bold;
            src: url('{{ str_replace('\\', '/', public_path('fonts/Carlito-Bold.ttf')) }}') format('truetype');
        }

        @page {
            size: letter landscape;
            margin: 1cm 1cm;
        }

        body {
            font-family: 'Carlito', sans-serif;
            font-size: 9pt;
            line-height: 1.25;
            color: #000;
            margin: 0;
        }

        h1 {
            font-size: 14pt;
            text-align: center;
            margin: 0 0 4pt;
        }

        h2 {
            font-size: 11pt;
            text-align: center;
            margin: 0 0 10pt;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4pt;
        }

        th {
            background-color: #f0f0f0;
            border: 1px solid #000;
            padding: 4pt;
            font-size: 8pt;
            text-align: center;
        }

        td {
            border: 1px solid #000;
            padding: 3pt 4pt;
            font-size: 8pt;
            vertical-align: top;
        }

        tr:nth-child(even) td {
            background-color: #fafafa;
        }

        .footer {
            margin-top: 12pt;
            font-size: 7pt;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>

    <h1>{{ $formato['nombre'] }}</h1>
    <h2>{{ $formato['clave'] }} - Reporte de Inventario de TI</h2>

    <table>
        <thead>
            <tr>
                <th style="width: 8%;">Folio</th>
                <th style="width: 15%;">Usuario</th>
                <th style="width: 12%;">Departamento</th>
                <th style="width: 14%;">Ubicación</th>
                <th style="width: 12%;">Tipo de Equipo</th>
                <th style="width: 10%;">No. Serie</th>
                <th style="width: 10%;">Marca</th>
                <th style="width: 10%;">Modelo</th>
                <th style="width: 8%;">Condición</th>
                <th style="width: 10%;">Fecha creación</th>
                <th style="width: 10%;">Creado por</th>
            </tr>
        </thead>
        <tbody>
            @forelse($equipos as $e)
            <tr>
                <td>{{ $e->folio }}</td>
                <td>{{ $e->usuario_asignado?->nombreCompleto() ?? '—' }}</td>
                <td>{{ $e->departamento ?? '—' }}</td>
                <td>{{ $e->ubicacion }}</td>
                <td>{{ $e->tipoEquipo->descripcion ?? '—' }}</td>
                <td>{{ $e->numeroSerie }}</td>
                <td>{{ $e->marca }}</td>
                <td>{{ $e->modelo }}</td>
                <td>{{ $e->estatus->descripcion ?? '—' }}</td>
                <td>{{ $e->fechaAlta->format('d/m/Y H:i') }}</td>
                <td>{{ $e->usuario_asignado?->nombreCompleto() ?? '—' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="11" style="text-align: center;">No hay activos de TI registrados.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        {{ $formato['clave'] }} - {{ now()->format('d/m/Y H:i') }} &nbsp;·&nbsp; Total: {{ $equipos->count() }} activo{{ $equipos->count() !== 1 ? 's' : '' }}
    </div>

</body>
</html>
