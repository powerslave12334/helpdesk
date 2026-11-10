<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>FO-IT-01 Responsiva ERP</title>
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

        /* Carta; márgenes del .docx: 1417 twips (2.5 cm) arriba/abajo,
           1701 twips (3 cm) izquierda/derecha. */
        @page {
            size: letter;
            margin: 2cm 3cm;
        }

        body {
            font-family: 'Carlito', sans-serif;
            font-size: 12pt;
            line-height: 1.16;
            color: #000;
            margin: 0;
        }

        p {
            margin: 0 0 0pt;
            text-align: justify;
        }

        .fecha {
            text-align: right;
        }

        .titulo {
            text-align: justify;
        }

        .datos-linea {
            text-align: left;
        }

        ul.condiciones {
            margin: 0 0 8pt;
            padding-left: 0.5in;
            list-style-type: disc;
        }

        ul.condiciones li {
            margin-bottom: 6pt;
            text-align: justify;
        }

        .espacio-firmas {
            height: 30pt;
        }

        table.firmas {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0;
        }

        table.firmas td {
            padding: 0;
            vertical-align: top;
            font-size: 12pt;
        }

        table.firmas td.sep {
            width: 4%;
        }

        table.firmas tr.lineas td {
            padding-bottom: 4pt;
        }

        table.firmas tr.etiquetas td {
            padding-top: 2pt;
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

    @if($cabeceraBase64 !== '')
    <img src="{{ $cabeceraBase64 }}" alt="FO-IT-01" style="width: 100%; margin-bottom: 2pt;">
    @endif

    <p class="fecha">Fecha: {{ $documento->fecha->format('d/m/Y') }}</p>

    <p class="datos-linea">Nombre del empleado responsable del manejo de clave:</p>
    <p class="datos-linea">{{ $documento->nombreEmpleado }}</p>
    <p class="datos-linea">Puesto: {{ $documento->puesto }} &nbsp;&nbsp;&nbsp; Correo: {{ $documento->correo }}</p>

    <p class="titulo">CONDICIONES Y POLÍTICAS DEL USO ADECUADO Y MANEJO DE CLAVES CONFIDENCIALES DEL SISTEMA DE PLANIFICACIÓN DE RECURSOS EMPRESARIALES (ERP).</p>

    <p>Con la finalidad de llevar el control adecuado de las claves confidenciales de la empresa, a continuación, se enlistan las características, condiciones y alineamientos para su manejo adecuado.</p>

    <ul class="condiciones">
        <li>Es responsabilidad del empleado en la empresa el buen uso y correcta aplicación de las mismas.</li>
        <li>Se entenderá como clave confidencial toda aquella clave de acceso al ERP que deberán mantenerse como confidencial y solo deberá ser usada por el personal previamente autorizado.</li>
        <li>La clave será manejada y controlada por el (“Empleado responsable”).</li>
        <li>El manejo de la clave que le sea proporcionada, solo será utilizado para servicios y actividades de la Empresa.</li>
        <li><strong>La clave y contraseña es de uso personal e intransferible.</strong></li>
    </ul>

    <p>El (“Empleado responsable”) firma de conformidad la presente carta responsiva y política de uso adecuado y manejo de claves confidenciales de esta empresa.</p>

    <p>La clave confidencial que se entrega al “Empleado responsable” es la siguiente:</p>

    <p class="datos-linea">Usuario: {{ $documento->usuarioErp }} &nbsp;&nbsp;&nbsp; Contraseña: {{ $documento->contrasenaErp }}</p>

    <div class="espacio-firmas"></div>

    <table class="firmas">
        <tr class="lineas">
            <td style="width: 48%;">
                @if($documento->firmaEmpleado)
                <img src="{{ $documento->firmaEmpleado }}" alt="Firma del empleado" style="height: 36pt; display: block;">
                @endif
                _______________________________
            </td>
            <td class="sep">&nbsp;</td>
            <td style="width: 48%;">
                @if($documento->firmaTi)
                <img src="{{ $documento->firmaTi }}" alt="Firma de TI" style="height: 36pt; display: block;">
                @endif
                __________________________________
            </td>
        </tr>
        <tr class="etiquetas">
            <td>&nbsp;&nbsp;&nbsp;&nbsp;Nombre y Firma
                @if($documento->fechaFirmaEmpleado)
                <div style="font-size: 9pt;">Fecha de firma: {{ $documento->fechaFirmaEmpleado->format('d/m/Y H:i') }}</div>
                @endif
            </td>
            <td class="sep">&nbsp;</td>
            <td>&nbsp;Área de Tecnologías de la Información
                @if($documento->fechaFirmaTi)
                <div style="font-size: 9pt;">Fecha de firma: {{ $documento->fechaFirmaTi->format('d/m/Y H:i') }}</div>
                @endif
            </td>
        </tr>
    </table>

    <p class="huella">
        Folio: {{ $documento->folio }} &nbsp;·&nbsp; Huella SHA-256: {{ $documento->huella ?? '—' }}
    </p>

</body>
</html>
