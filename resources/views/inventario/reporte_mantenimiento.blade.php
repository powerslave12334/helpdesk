<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            margin: 0;
            padding: 0px;
        }

        h2 {
            font-size: 12px;
            margin: 15px 0 8px;
            color: #0f172a;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 0px;
        }

        .report-title {
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            color: #0f172a;
            margin: 8px 0 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .info-table td {
            padding: 0px;
            border: 1px solid #e2e8f0;
            font-size: 10px;
        }

        .info-table td.label {
            background: #f1f5f9;
            font-weight: bold;
            width: 140px;
            color: #475569;
        }

        .check-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }

        .check-table th {
            background: #f1f5f9;
            padding: 5px 8px;
            border: 1px solid #e2e8f0;
            text-align: left;
            font-size: 10px;
            font-weight: bold;
        }

        .check-table td {
            padding: 4px 8px;
            border: 1px solid #e2e8f0;
            font-size: 10px;
        }

        .checkbox {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 1px solid #64748b;
            text-align: center;
            line-height: 11px;
            font-size: 9px;
        }

        .checkbox.checked {
            background: #2563eb;
            color: white;
            border-color: #2563eb;
        }

        .section-box {
            border: 1px solid #e2e8f0;
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }

        .activities {
            columns: 2;
            column-gap: 20px;
        }

        .activity-item {
            margin-bottom: 4px;
            font-size: 10px;
        }

        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            padding-top: 20px;
        }

        .signature-box {
            width: 45%;
            text-align: center;
        }

        .signature-line {
            border-top: 1px solid #1a1a1a;
            margin-top: 50px;
            padding-top: 5px;
        }

        .footer {
            text-align: center;
            font-size: 9px;
            color: #94a3b8;
            margin-top: 20px;
            border-top: 1px solid #e2e8f0;
            padding-top: 5px;
        }

    </style>
</head>

<body>
    <!-- CABECERA -->
    <table style="border:none; width:100%; margin-bottom: 12px;">
        <tr>
            <td
                style="border:none; text-align:left; vertical-align:top; font-size:10px; color:#475569; line-height:1.8; width:30%;">
                <strong>CÓDIGO:</strong> IT -CC-02<br>
                <strong>Versión:</strong> 0.0<br>
                <strong>Actualizado:</strong> {{ now()->format('d/m/Y') }}
            </td>
            <td style="border:none; text-align:center; vertical-align:middle; width:40%;">
                <div style="font-size:13px; font-weight:bold; color:#0f172a; line-height:1.4;">
                    REPORTE DE<br>MANTENIMIENTO A EQUIPO
                </div>
            </td>
            <td style="border:none; text-align:right; vertical-align:top; width:30%;">
                @if($logoBase64)
                <img src="{{ $logoBase64 }}" style="width:110px; height:auto;">
                @endif
            </td>
        </tr>
    </table>
    <h2>INFORMACIÓN DEL EQUIPO</h2>
    <table class="info-table">
        <tr>
            <td class="label">Nombre del Usuario:</td>
            <td>{{ $usuarioAsignado }}</td>
            <td class="label">Área / Departamento:</td>
            <td>{{ $area ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Marca:</td>
            <td>{{ $equipo->marca ?? 'N/A' }}</td>
            <td class="label">Modelo:</td>
            <td>{{ $equipo->modelo ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Serie Interno:</td>
            <td>{{ $equipo->numeroSerie ?? 'N/A' }}</td>
            <td class="label">Nombre Equipo:</td>
            <td>{{ $equipo->nombreEquipo ?? 'N/A' }}</td>
        </tr>
    </table>

    <!-- TABLA CONTENEDORA DE 2 COLUMNAS (50% / 50%) -->
    <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
        <tr>
            <!-- SECCIÓN 1: TIPO DE SOLICITUD -->
            <td style="width: 50%; vertical-align: top; padding-right: 10px;">
                <h2>TIPO DE SOLICITUD</h2>
                @php
                $tipoActual = $mantenimiento->tipoMantenimiento ?? 'Preventivo';
                @endphp
                <table class="info-table" style="width: 100%;">
                    <tr>
                        <td style="width: 33.33%;"><span
                                class="checkbox {{ $tipoActual == 'Preventivo' ? 'checked' : '' }}">{{ $tipoActual == 'Preventivo' ? 'X' : '' }}</span>
                            Mantenimiento Preventivo</td>
                        <td style="width: 33.33%;"><span
                                class="checkbox {{ $tipoActual == 'Correctivo' ? 'checked' : '' }}">{{ $tipoActual == 'Correctivo' ? 'X' : '' }}</span>
                            Mantenimiento Correctivo</td>
                        <td style="width: 33.33%;"><span
                                class="checkbox {{ $tipoActual == 'Verificación' ? 'checked' : '' }}">{{ $tipoActual == 'Verificación' ? 'X' : '' }}</span>
                            Verificación</td>
                    </tr>
                </table>
            </td>

            <!-- SECCIÓN 2: ESTADO INICIAL DEL EQUIPO -->
            <td style="width: 50%; vertical-align: top; padding-left: 10px;">
                <h2>ESTADO INICIAL DEL EQUIPO</h2>
                @php
                $estadoInicial = $mantenimiento->estadoInicial ?? 'Funcional';
                $estados = ['Funcional', 'Funcional con fallas', 'No enciende', 'Otro'];
                @endphp
                <table class="info-table" style="width: 100%;">
                    <tr>
                        @foreach($estados as $estado)
                        <td style="width: {{ 100/count($estados) }}%;"><span
                                class="checkbox {{ $estadoInicial == $estado ? 'checked' : '' }}">{{ $estadoInicial == $estado ? 'X' : '' }}</span> {{ $estado }}
                        </td>
                        @endforeach
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- TABLA CONTENEDORA DE 2 COLUMNAS (50% / 50%) -->
    <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
        <tr>
            <!-- SECCIÓN 1: PRUEBAS DE FUNCIONAMIENTO (HARDWARE) -->
            <td style="width: 50%; vertical-align: top; padding-right: 10px;">
                <h2>PRUEBAS DE FUNCIONAMIENTO (HARDWARE)</h2>
                @php
                $hwFields = [
                'hw_encendido' => 'Encendido / Apagado',
                'hw_pantalla' => 'Pantalla',
                'hw_ventilacion' => 'Ventilación y ruidos',
                'hw_dispositivos' => 'Dispositivos y periféricos',
                ];
                @endphp
                <div class="section-box">
                    <table class="check-table" style="width: 100%;">
                        <tr>
                            <th style="width:50%">Detalle</th>
                            <th style="width:30px;text-align:center">B</th>
                            <th style="width:30px;text-align:center">R</th>
                            <th style="width:30px;text-align:center">M</th>
                            <th style="width:30px;text-align:center">NA</th>
                        </tr>
                        @foreach($hwFields as $field => $label)
                        @php $val = $mantenimiento->$field ?? ''; @endphp
                        <tr>
                            <td>{{ $label }}</td>
                            <td style="text-align:center"><span
                                    class="checkbox {{ $val == 'B' ? 'checked' : '' }}">{{ $val == 'B' ? 'X' : '' }}</span></td>
                            <td style="text-align:center"><span
                                    class="checkbox {{ $val == 'R' ? 'checked' : '' }}">{{ $val == 'R' ? 'X' : '' }}</span></td>
                            <td style="text-align:center"><span
                                    class="checkbox {{ $val == 'M' ? 'checked' : '' }}">{{ $val == 'M' ? 'X' : '' }}</span></td>
                            <td style="text-align:center"><span
                                    class="checkbox {{ $val == 'NA' ? 'checked' : '' }}">{{ $val == 'NA' ? 'X' : '' }}</span></td>
                        </tr>
                        @endforeach
                    </table>
                </div>
            </td>

            <!-- SECCIÓN 2: VERIFICACIÓN DE SOFTWARE -->
            <td style="width: 50%; vertical-align: top; padding-left: 10px;">
                <h2>VERIFICACIÓN DE SOFTWARE</h2>
                @php
                $swFields = [
                'sw_so' => 'S.O. Actualizado',
                'sw_seguridad' => 'Actualizaciones de seguridad',
                'sw_antivirus' => 'Antivirus Actualizado',
                'sw_rendimiento' => 'Rendimiento general del sistema',
                ];
                @endphp
                <div class="section-box">
                    <table class="check-table" style="width: 100%;">
                        <tr>
                            <th style="width:50%">Detalle</th>
                            <th style="width:30px;text-align:center">B</th>
                            <th style="width:30px;text-align:center">R</th>
                            <th style="width:30px;text-align:center">M</th>
                            <th style="width:30px;text-align:center">NA</th>
                        </tr>
                        @foreach($swFields as $field => $label)
                        @php $val = $mantenimiento->$field ?? ''; @endphp
                        <tr>
                            <td>{{ $label }}</td>
                            <td style="text-align:center"><span
                                    class="checkbox {{ $val == 'B' ? 'checked' : '' }}">{{ $val == 'B' ? 'X' : '' }}</span></td>
                            <td style="text-align:center"><span
                                    class="checkbox {{ $val == 'R' ? 'checked' : '' }}">{{ $val == 'R' ? 'X' : '' }}</span></td>
                            <td style="text-align:center"><span
                                    class="checkbox {{ $val == 'M' ? 'checked' : '' }}">{{ $val == 'M' ? 'X' : '' }}</span></td>
                            <td style="text-align:center"><span
                                    class="checkbox {{ $val == 'NA' ? 'checked' : '' }}">{{ $val == 'NA' ? 'X' : '' }}</span></td>
                        </tr>
                        @endforeach
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <!-- TABLA CONTENEDORA DE 2 COLUMNAS (50% / 50%) -->
    <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
        <tr>
            <!-- SECCIÓN 1: ACTIVIDADES REALIZADAS -->
            <td style="width: 50%; vertical-align: top; padding-right: 10px;">
                <h2>ACTIVIDADES REALIZADAS</h2>
                @php
                $actividades = [
                'act_limpieza' => 'Limpieza física interna y externa',
                'act_revision_hw' => 'Revisión de componentes de hardware',
                'act_actualizacion_so' => 'Actualización de sistema operativo',
                'act_instalacion_sw' => 'Instalación / actualización de software',
                'act_parches' => 'Instalación de parches de seguridad',
                'act_eliminacion' => 'Eliminación de programas no autorizados',
                'act_diagnostico' => 'Diagnóstico y corrección de fallas',
                ];
                @endphp
                <div class="section-box">
                    <div class="activities">
                        @foreach($actividades as $field => $label)
                        <div class="activity-item"><span
                                class="checkbox {{ ($mantenimiento->$field ?? false) ? 'checked' : '' }}">{{ ($mantenimiento->$field ?? false) ? 'X' : '' }}</span>
                            {{ $label }}</div>
                        @endforeach
                        @if($mantenimiento->act_otro ?? false)
                        <div class="activity-item"><span class="checkbox checked">X</span> Otro:
                            {{ $mantenimiento->act_otro_detalle ?? 'N/A' }}</div>
                        @else
                        <div class="activity-item"><span class="checkbox"></span> Otro (especifique): _______________
                        </div>
                        @endif
                    </div>
                </div>
            </td>

            <!-- SECCIÓN 2: CONCLUSIÓN DEL SERVICIO -->
            <td style="width: 50%; vertical-align: top; padding-left: 10px;">
                <h2>CONCLUSIÓN DEL SERVICIO</h2>
                @php
                $conclusion = $mantenimiento->conclusion ?? 'Equipo funcional';
                $conclusiones = ['Equipo funcional', 'Equipo funcional con observaciones', 'Equipo pendiente de
                refacciones / seguimiento'];
                @endphp
                <div class="section-box">
                    <table class="info-table" style="width: 100%;">
                        @foreach($conclusiones as $c)
                        <tr>
                            <td style="padding: 6px 0;">
                                <span class="checkbox {{ $conclusion == $c ? 'checked' : '' }}">{{ $conclusion == $c ? 'X' : '' }}</span> {{ $c }}
                            </td>
                        </tr>
                        @endforeach
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <h2>DETALLE DEL DIAGNÓSTICO</h2>
    <div class="section-box">
        <p style="margin:0;min-height:60px">{{ $mantenimiento->observaciones ?? 'Sin observaciones registradas.' }}</p>
    </div>
    
    <!-- SECCIÓN DE FIRMAS (50% / 50%) -->
    <table style="width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 30px;">
        <tr>
            <!-- FIRMA TÉCNICO / SOPORTE TI -->
            <td style="width: 50%; vertical-align: bottom; text-align: center; padding-right: 15px;">
                <div class="signature-box"
                    style="padding-top: 5px; display: inline-block; width: 80%;">
                    <div class="signature-line">
                        <strong>Firma responsable del mantenimiento</strong><br>
                        <small>{{ $tecnico->nombre ?? 'N/A' }} {{ $tecnico->app ?? '' }}</small><br>
                        <small style="color:#64748b">Soporte TI</small>
                    </div>
                </div>
            </td>

            <!-- FIRMA USUARIO / RESPONSABLE DEL EQUIPO -->
            <td style="width: 50%; vertical-align: bottom; text-align: center; padding-left: 15px;">
                <div class="signature-box"
                    style="padding-top: 5px; display: inline-block; width: 80%;">
                    <div class="signature-line">
                        <strong>Firma responsable del equipo</strong><br>
                        <small>{{ $usuarioAsignado }}</small><br>
                        <small style="color:#64748b">{{ $area ?? '' }}</small>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Reporte generado el {{ now()->format('d/m/Y H:i') }} — Sistema HelpDesk
    </div>
</body>

</html>
