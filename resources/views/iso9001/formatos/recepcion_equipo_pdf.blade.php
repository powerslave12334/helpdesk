<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>FO-IT-03 Recepción de equipo</title>
    <style>
        /* Fuente del documento oficial: Calibri. Carlito es su equivalente
           libre con métricas idénticas (mismos saltos de línea y ancho). */
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

        /* A4; márgenes del .xlsx: 0.7" izquierda/derecha,
           0.75" arriba/abajo (pageMargins). */
        @page {
            size: A4;
            margin: 0.75in 0.7in;
        }

        body {
            font-family: Carlito, Calibri, sans-serif;
            font-size: 11pt;
            line-height: 1.25;
            color: #000;
            margin: 0;
        }

        .cabecera {
            width: 100%;
            display: block;
        }

        .fecha {
            font-weight: bold;
            margin: 10pt 0 0;
        }

        table.seccion {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8pt;
        }

        table.seccion td {
            vertical-align: top;
            padding: 0;
        }

        .titulo {
            font-weight: bold;
            margin: 0 0 4pt;
        }

        /* Casillas de los grupos de checkboxes (cuadrado con borde;
           marcada = relleno negro, sin depender de glifos de fuente). */
        .casilla {
            display: inline-block;
            width: 7pt;
            height: 7pt;
            border: 1px solid #000;
            background-color: #fff;
            vertical-align: -1pt;
            margin-right: 4pt;
        }

        .casilla.marcada {
            background-color: #000;
        }

        .opcion {
            font-size: 8pt;
            margin: 0 0 3pt;
            white-space: nowrap;
        }

        .opcion-larga {
            white-space: normal;
        }

        .grupo-opciones {
            display: inline-block;
            vertical-align: top;
        }

        /* El formato oficial imprime una etiqueta por renglón
           (Marca / Modelo / Numero de serie / IMEI y, en 9pt,
           Numero telefonico). */
        .campo {
            margin: 0;
            white-space: nowrap;
        }

        .campo-menor {
            font-size: 9pt;
        }

        /* Separadores punteados de las filas 12, 22 y 52 del .xlsx. */
        .separador {
            border-bottom: 1px dotted #000;
            margin-top: 10pt;
        }

        .descripcion-texto {
            font-weight: bold;
            margin: 4pt 0 0;
        }

        .gastos-monto {
            font-weight: bold;
            font-size: 20pt;
            margin: 2pt 0 0;
        }

        table.firmas {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14pt;
        }

        table.firmas td {
            vertical-align: top;
            padding: 0;
        }

        .etiqueta {
            font-weight: bold;
            margin-bottom: 12pt;
        }

        /* Caja de firma de altura fija: ambas líneas (fila 58 del .xlsx)
           quedan alineadas aunque un firmante no tenga imagen. */
        .linea {
            border-bottom: 1px solid #000;
            height: 40pt;
            text-align: center;
        }

        .zona-firma {
            height: 24pt;
        }

        .zona-firma img {
            display: block;
            height: 24pt;
            margin: 0 auto;
        }

        .nombre-linea {
            text-align: center;
            font-size: 11pt;
        }

        .rol {
            font-weight: bold;
            margin-top: 2pt;
        }

        .fecha-firma {
            font-size: 8pt;
            color: #333;
            margin-top: 1pt;
        }

        .huella {
            font-size: 8pt;
            color: #444;
            text-align: center;
            margin-top: 8pt;
        }
    </style>
</head>
<body>

    @if($cabeceraBase64 !== '')
    <img src="{{ $cabeceraBase64 }}" alt="FO-IT-03" class="cabecera">
    @endif

    <p class="fecha">FECHA: {{ $documento->fecha->format('d/m/Y') }}</p>

    {{-- Filas 5-12: motivo (selección única) y tipo de activo (múltiple). --}}
    <table class="seccion">
        <tr>
            <td style="width: 53%;">
                <div class="titulo">Motivo de la Entrega:</div>
                @foreach(\App\Models\IsoRecepcionEquipo::MOTIVOS as $opcion)
                <div class="opcion">
                    <span class="casilla{{ $documento->motivoEntrega === $opcion ? ' marcada' : '' }}"></span>{{ $opcion }}
                </div>
                @endforeach
            </td>
            <td style="width: 47%;">
                <div class="titulo">Tipo de activo entregado:</div>
                @foreach(\App\Models\IsoRecepcionEquipo::tiposEnColumnas() as $columna)
                <div class="grupo-opciones" style="width: 50%;">
                    @foreach($columna as $opcion)
                    <div class="opcion{{ in_array($opcion, ['Kit inalambrico Mouse', 'Kit inalambrico Teclado']) ? ' opcion-larga' : '' }}">
                        <span class="casilla{{ in_array($opcion, $documento->tiposActivos()) ? ' marcada' : '' }}"></span>{{ $opcion }}
                    </div>
                    @endforeach
                </div>
                @endforeach
            </td>
        </tr>
    </table>

    <div class="separador"></div>

    {{-- Filas 13-21: identificación del activo y accesorios. --}}
    <table class="seccion">
        <tr>
            <td style="width: 53%;">
                <div class="titulo">Identificacion del Activo:</div>
                <div class="campo">Marca: {{ $documento->marca }}</div>
                <div class="campo">Modelo: {{ $documento->modelo }}</div>
                <div class="campo">Numero de serie: {{ $documento->noSerie }}</div>
                <div class="campo">IMEI: {{ $documento->imei ?? '' }}</div>
                <div class="campo campo-menor">Numero telefonico: {{ $documento->numeroTelefonico ?? '' }}</div>
            </td>
            <td style="width: 47%;">
                <div class="titulo">Accesorios del Activo:</div>
                @foreach(\App\Models\IsoRecepcionEquipo::accesoriosEnColumnas() as $columna)
                <div class="grupo-opciones" style="width: {{ $loop->first ? 55 : 45 }}%;">
                    @foreach($columna as $opcion)
                    <div class="opcion">
                        <span class="casilla{{ in_array($opcion, $documento->accesoriosMarcados()) ? ' marcada' : '' }}"></span>{{ $opcion }}
                    </div>
                    @endforeach
                </div>
                @endforeach
            </td>
        </tr>
    </table>

    <div class="separador"></div>

    {{-- Filas 23-44: descripción de la entrega (área de texto libre). --}}
    <div class="titulo">Descripcion de la Entrega :</div>
    <div class="descripcion-texto">{{ $documento->descripcionEntrega }}</div>

    <div class="separador" style="margin-top: 40pt;"></div>

    {{-- Filas 44-52: aplicación de gastos y monto. --}}
    <table class="seccion">
        <tr>
            <td style="width: 53%;">
                <div class="titulo">Aplicacion de Gastos de Reparación:</div>
                @foreach(\App\Models\IsoRecepcionEquipo::APLICACION_GASTOS as $opcion)
                <div class="opcion">
                    <span class="casilla{{ $documento->aplicacionGastos === $opcion ? ' marcada' : '' }}"></span>{{ $opcion }}
                </div>
                @endforeach
            </td>
            <td style="width: 19%;"></td>
            <td style="width: 28%;">
                <div class="titulo">Gastos de Reparación:</div>
                @php
                // El formato imprime "$ 0" sin decimales cuando es entero.
                $gastos = (float) $documento->gastosReparacion;
                @endphp
                <div class="gastos-monto">$ {{ number_format($gastos, fmod($gastos, 1) === 0.0 ? 0 : 2, '.', ',') }}</div>
            </td>
        </tr>
    </table>

    <div class="separador"></div>

    {{-- Filas 56-59: firmas con el rol fijo del formato debajo de la línea. --}}
    <table class="firmas">
        <tr>
            <td style="width: 34%;">
                <div class="etiqueta">Realiza Revisión:</div>
                <div class="linea">
                    <div class="zona-firma">
                        @if($documento->firmaRevision)
                        <img src="{{ $documento->firmaRevision }}" alt="Firma de revisión">
                        @endif
                    </div>
                    <div class="nombre-linea">{{ $documento->revisadoPor }}</div>
                </div>
                <div class="rol">Jefe de Sistemas</div>
                @if($documento->fechaFirmaRevision)
                <div class="fecha-firma">Fecha de firma: {{ $documento->fechaFirmaRevision->format('d/m/Y H:i') }}</div>
                @endif
            </td>
            <td style="width: 38%;">&nbsp;</td>
            <td style="width: 28%;">
                <div class="etiqueta">Entrega Activo:</div>
                <div class="linea">
                    <div class="zona-firma">
                        @if($documento->firmaEntrega)
                        <img src="{{ $documento->firmaEntrega }}" alt="Firma de entrega">
                        @endif
                    </div>
                    <div class="nombre-linea">{{ $documento->entregadoPor }}</div>
                </div>
                <div class="rol">Persona</div>
                @if($documento->fechaFirmaEntrega)
                <div class="fecha-firma">Fecha de firma: {{ $documento->fechaFirmaEntrega->format('d/m/Y H:i') }}</div>
                @endif
            </td>
        </tr>
    </table>

    <p class="huella">
        Folio: {{ $documento->folio }} &nbsp;·&nbsp; Huella SHA-256: {{ $documento->huella ?? '—' }}
    </p>

</body>
</html>
