<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>FO-IT-02 Acta de entrega</title>
    <style>
        /* Fuente del documento oficial: Arial. Helvetica es su equivalente
           métrico incluido en dompdf (mismos saltos de línea y ancho). */
        @page {
            size: letter;
            /* Márgenes del .xlsx: 0.25" arriba/abajo, 0.75" izquierda/derecha. */
            margin: 0.25in 0.75in;
        }

        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 12pt;
            line-height: 1.2;
            color: #000;
            margin: 0;
        }

        .fecha {
            margin: 8pt 0 0;
        }

        .intro {
            margin: 16pt 0 0;
        }

        table.equipos {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10pt;
        }

        table.equipos th,
        table.equipos td {
            border: 1px solid #000;
            padding: 3pt 4pt;
            font-size: 12pt;
            text-align: center;
            font-weight: bold;
        }

        .legal {
            margin-top: 16pt;
            font-weight: bold;
            text-align: left;
        }

        .legal p {
            margin: 0 0 8pt;
        }

        /* Hueco de las filas 22-29 del .xlsx antes de las firmas. */
        .espacio-firmas {
            height: 110pt;
        }

        table.firmas {
            width: 100%;
            border-collapse: collapse;
        }

        table.firmas td {
            vertical-align: top;
        }

        .etiqueta {
            text-align: center;
            font-size: 12pt;
        }

        .caja-firma {
            height: 46pt;
            border-bottom: 1px solid #000;
            text-align: center;
        }

        .caja-firma img {
            height: 34pt;
            display: block;
            margin: 0 auto;
        }

        .nombre {
            text-align: center;
            font-size: 12pt;
            padding-top: 3pt;
        }

        .fecha-firma {
            text-align: center;
            font-size: 9pt;
            color: #333;
        }

        .huella {
            font-size: 8pt;
            color: #444;
            text-align: center;
            margin-top: 10pt;
        }
    </style>
</head>
<body>

    @if($cabeceraBase64 !== '')
    <img src="{{ $cabeceraBase64 }}" alt="FO-IT-02" style="width: 100%; display: block;">
    @endif

    <p class="fecha">Fecha: {{ $documento->fecha->format('d/m/Y') }}</p>

    <p class="intro">Con la presente acta se le hace entrega de lo siguiente:</p>

    @php
        // El formato oficial trae 4 renglones con borde: se muestran los
        // capturados y se rellena hasta 4 para conservar el diseño.
        $renglones = $documento->equipos->values();
    @endphp

    <table class="equipos">
        <thead>
            {{-- Proporciones de las columnas A-F del .xlsx oficial. --}}
            <tr>
                <th style="width: 14.23%;">Cantidad</th>
                <th style="width: 14.23%;">Descripción</th>
                <th style="width: 13.73%;">Marca</th>
                <th style="width: 18.89%;">Modelo</th>
                <th style="width: 24.18%;">No. De serie</th>
                <th style="width: 14.73%;">Condiciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($renglones as $renglon)
            <tr>
                <td>{{ $renglon->cantidad }}</td>
                <td>{{ $renglon->descripcion }}</td>
                <td>{{ $renglon->marca ?? '' }}</td>
                <td>{{ $renglon->modelo ?? '' }}</td>
                <td>{{ $renglon->noSerie ?? '' }}</td>
                <td>{{ $renglon->condiciones }}</td>
            </tr>
            @endforeach
            @for($i = $renglones->count(); $i < 4; $i++)
            <tr>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
            </tr>
            @endfor
        </tbody>
    </table>

    {{-- Texto legal fijo del formato oficial (A13:G21, transcripción literal). --}}
    <div class="legal">
        <p>El trabajador manifiesta que:</p>
        <p>El / los equipos que aquí se describe (n), son y serán de la empresa en todo momento, en caso de terminación del contrato de trabajo o entrega de un nuevo equipo, me comprometo a hacer la devolución de forma inmediata.En caso de daño del equipo, el trabajador debera reponer el daño a la empresa.</p>
        <p>Autorizo expresamente a la empresa mediante este documento a descontar de salarios y liquidación de prestaciones los valores de la dotación cuando en cualquiera de los casos anteriores no la devuelve al empleador.</p>
    </div>

    <div class="espacio-firmas"></div>

    <table class="firmas">
        <tr>
            <td style="width: 25%;">
                <div class="etiqueta">Entregado por:</div>
                <div class="caja-firma">
                    @if($documento->firmaEntrega)
                    <img src="{{ $documento->firmaEntrega }}" alt="Firma de entrega">
                    @endif
                </div>
                <div class="nombre">{{ $documento->entregadoPor }}</div>
                @if($documento->fechaFirmaEntrega)
                <div class="fecha-firma">Fecha de firma: {{ $documento->fechaFirmaEntrega->format('d/m/Y H:i') }}</div>
                @endif
            </td>
            <td style="width: 50%;">&nbsp;</td>
            <td style="width: 25%;">
                <div class="etiqueta">Recibido por:</div>
                <div class="caja-firma">
                    @if($documento->firmaRecepcion)
                    <img src="{{ $documento->firmaRecepcion }}" alt="Firma de recepción">
                    @endif
                </div>
                <div class="nombre">{{ $documento->recibidoPor }}</div>
                @if($documento->fechaFirmaRecepcion)
                <div class="fecha-firma">Fecha de firma: {{ $documento->fechaFirmaRecepcion->format('d/m/Y H:i') }}</div>
                @endif
            </td>
        </tr>
    </table>

    <p class="huella">
        Folio: {{ $documento->folio }} &nbsp;·&nbsp; Huella SHA-256: {{ $documento->huella ?? '—' }}
    </p>

</body>
</html>
