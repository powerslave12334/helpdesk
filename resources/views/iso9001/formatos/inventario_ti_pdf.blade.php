<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $formato['clave'] }} - {{ $equipo->folio }}</title>
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
            size: letter;
            margin: 2cm 3cm;
        }

        body {
            font-family: 'Carlito', sans-serif;
            font-size: 11pt;
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

        table.datos {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8pt;
        }

        table.datos td {
            border: 1px solid #000;
            padding: 4pt 6pt;
            vertical-align: top;
        }

        table.datos td.campo {
            width: 30%;
            font-weight: bold;
            background-color: #f0f0f0;
        }

        .fecha {
            text-align: right;
            margin: 10pt 0 0;
        }

        .huella {
            font-size: 8pt;
            color: #444;
            text-align: center;
            margin-top: 12pt;
        }
    </style>
</head>
<body>

    <h1>{{ $formato['nombre'] }}</h1>
    <h2>{{ $formato['clave }} - {{ $equipo->folio }}</h2>

    <p class="fecha">Fecha: {{ $equipo->fechaAlta->format('d/m/Y H:i') }}</p>

    <table class="datos">
        <tr>
            <td class="campo">Usuario:</td>
            <td>{{ $equipo->usuario_asignado?->nombreCompleto() ?? '—' }}</td>
        </tr>
        <tr>
            <td class="campo">Departamento:</td>
            <td>{{ $equipo->departamento ?? '—' }}</td>
        </tr>
        <tr>
            <td class="campo">Ubicación:</td>
            <td>{{ $equipo->ubicacion }}</td>
        </tr>
        <tr>
            <td class="campo">Tipo de Equipo:</td>
            <td>{{ $equipo->tipoEquipo->descripcion ?? '—' }}</td>
        </tr>
        <tr>
            <td class="campo">No. Serie:</td>
            <td>{{ $equipo->numeroSerie }}</td>
        </tr>
        <tr>
            <td class="campo">Marca:</td>
            <td>{{ $equipo->marca }}</td>
        </tr>
        <tr>
            <td class="campo">Modelo:</td>
            <td>{{ $equipo->modelo }}</td>
        </tr>
        <tr>
            <td class="campo">Condición:</td>
            <td>{{ $equipo->estatus->descripcion ?? '—' }}</td>
        </tr>
        <tr>
            <td class="campo">Registrado por:</td>
            <td>{{ $equipo->usuario_asignado?->nombreCompleto() ?? '—' }}</td>
        </tr>
    </table>

    <p class="huella">
        Folio: {{ $equipo->folio }} &nbsp;·&nbsp; Estado: activo
    </p>

</body>
</html>
