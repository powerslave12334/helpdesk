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
    <h2>{{ $formato['clave'] }} - Reporte de Inventario</h2>

    <table>
        <thead>
            <tr>
                <th style="width: 8%;">Folio</th>
                <th style="width: 12%;">Código</th>
                <th style="width: 20%;">Nombre</th>
                <th style="width: 12%;">Marca</th>
                <th style="width: 12%;">Modelo</th>
                <th style="width: 10%;">Serie</th>
                <th style="width: 10%;">Inventario</th>
                <th style="width: 14%;">Ubicación</th>
                <th style="width: 8%;">Estado</th>
                <th style="width: 10%;">Fecha adq.</th>
                <th style="width: 10%;">Creado por</th>
            </tr>
        </thead>
        <tbody>
            @forelse($herramientas as $h)
            <tr>
                <td>{{ $h->folio }}</td>
                <td>{{ $h->codigo }}</td>
                <td>{{ $h->nombre }}</td>
                <td>{{ $h->marca }}</td>
                <td>{{ $h->modelo }}</td>
                <td>{{ $h->numeroSerie ?? '—' }}</td>
                <td>{{ $h->numeroInventario ?? '—' }}</td>
                <td>{{ $h->ubicacion }}</td>
                <td>{{ $h->estado_label }}</td>
                <td>{{ $h->fechaAdquisicion?->format('d/m/Y') ?? '—' }}</td>
                <td>{{ $h->creador?->nombreCompleto() ?? '—' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="11" style="text-align: center;">No hay herramientas registradas.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        {{ $formato['clave'] }} - {{ now()->format('d/m/Y H:i') }} &nbsp;·&nbsp; Total: {{ $herramientas->count() }} herramienta{{ $herramientas->count() !== 1 ? 's' : '' }}
    </div>

</body>
</html>
