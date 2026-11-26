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

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }

        .data-table th {
            background: #f1f5f9;
            padding: 5px 6px;
            border: 1px solid #e2e8f0;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
            color: #475569;
        }

        .data-table td {
            padding: 4px 6px;
            border: 1px solid #e2e8f0;
            font-size: 9px;
            vertical-align: top;
        }

        .section-box {
            border: 1px solid #e2e8f0;
            padding: 8px 10px;
            margin: 6px 0 12px;
            border-radius: 4px;
        }

        .detail-title {
            font-size: 10px;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 4px;
        }

        .detail-meta {
            font-size: 9px;
            color: #475569;
            margin-bottom: 5px;
        }

        .activities {
            font-size: 9px;
            line-height: 1.6;
        }

        .checkbox {
            display: inline-block;
            width: 11px;
            height: 11px;
            border: 1px solid #64748b;
            text-align: center;
            line-height: 10px;
            font-size: 8px;
            margin-right: 3px;
        }

        .checkbox.checked {
            background: #2563eb;
            color: white;
            border-color: #2563eb;
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

        $primeraItem = $mantenimientos->first();
        $ultimoItem = $mantenimientos->last();
        $primeraFecha = $primeraItem ? $primeraItem['mantenimiento']->fechaEjecucion : null;
        $ultimaFecha = $ultimoItem ? $ultimoItem['mantenimiento']->fechaEjecucion : null;
    @endphp

    <div style="text-align:center; margin-bottom: 12px;">
        <div style="font-size:13px; font-weight:bold; color:#0f172a; line-height:1.4;">
            REPORTE GENERAL DE<br>MANTENIMIENTOS REALIZADOS
        </div>
    </div>

    <!-- RESUMEN -->
    <h2>RESUMEN GENERAL</h2>
    <table class="info-table">
        <tr>
            <td class="label">Mantenimientos completados:</td>
            <td>{{ $mantenimientos->count() }}</td>
            <td class="label">Periodo del reporte:</td>
            <td>
                @if($primeraFecha && $ultimaFecha)
                    {{ $primeraFecha->format('d/m/Y') }} al {{ $ultimaFecha->format('d/m/Y') }}
                @else
                    N/A
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Equipos intervenidos:</td>
            <td>{{ $mantenimientos->count() }}</td>
        </tr>
    </table>

    <!-- 1. MANTENIMIENTOS HECHOS -->
    <h2>1. MANTENIMIENTOS REALIZADOS</h2>
    @if($mantenimientos->isEmpty())
        <div class="section-box">No hay mantenimientos completados para mostrar.</div>
    @else
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:4%">N°</th>
                <th style="width:18%">Equipo</th>
                <th style="width:16%">Usuario asignado</th>
                <th style="width:12%">Departamento</th>
                <th style="width:12%">Técnico</th>
                <th style="width:10%">Tipo</th>
                <th style="width:12%">F. Ejecución</th>
                <th style="width:16%">Conclusión</th>
            </tr>
        </thead>
        <tbody>
            @foreach($mantenimientos as $index => $item)
            @php $m = $item['mantenimiento']; @endphp
            <tr>
                <td style="text-align:center">{{ $index + 1 }}</td>
                <td>{{ $item['nombreEquipo'] }}</td>
                <td>{{ $item['usuarioAsignado'] }}</td>
                <td><small style="color:#64748b">{{ $item['area'] }}</small></td>
                <td>{{ $item['tecnicoNombre'] }}</td>
                <td>{{ $m->tipoMantenimiento }}</td>
                <td>{{ $m->fechaEjecucion->format('d/m/Y') }}</td>
                <td>{{ $m->conclusion ?? 'Sin conclusión' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <!-- 3. TIEMPO DE MANTENIMIENTO RECOMENDADO -->
    <h2>2. TIEMPO DE MANTENIMIENTO RECOMENDADO</h2>
    @if($mantenimientos->isEmpty())
        <div class="section-box">No hay mantenimientos registrados para mostrar.</div>
    @else
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:4%">N°</th>
                <th style="width:18%">Equipo</th>
                <th style="width:16%">Usuario asignado</th>
                <th style="width:14%">Departamento</th>
                <th style="width:18%">Periodo recomendado</th>
                <th style="width:16%">Último mantenimiento</th>
                <th style="width:18%">Próxima fecha sugerida</th>
            </tr>
        </thead>
        <tbody>
            @foreach($mantenimientos as $index => $item)
            @php $m = $item['mantenimiento']; @endphp
            <tr>
                <td style="text-align:center">{{ $index + 1 }}</td>
                <td>{{ $item['nombreEquipo'] }}</td>
                <td>{{ $item['usuarioAsignado'] }}</td>
                <td><small style="color:#64748b">{{ $item['area'] }}</small></td>
                <td>Cada 2 meses<br><small style="color:#64748b">Bimestral</small></td>
                <td>{{ $m->fechaEjecucion->format('d/m/Y') }}</td>
                <td>{{ $item['proximaFechaSugerida'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <!-- 2. LO QUE SE REALIZÓ EN CADA MANTENIMIENTO -->
    <h2>3. ACTIVIDADES REALIZADAS POR MANTENIMIENTO</h2>
    @if($mantenimientos->isEmpty())
        <div class="section-box">No hay actividades registradas.</div>
    @else
        @foreach($mantenimientos as $index => $item)
        @php $m = $item['mantenimiento']; @endphp
        <div class="section-box">
            <div class="detail-title">{{ $index + 1 }}. {{ $item['nombreEquipo'] }} — {{ $m->tipoMantenimiento }}</div>
            <div class="detail-meta">
                Técnico: {{ $item['tecnicoNombre'] }} ·
                Ejecutado: {{ $m->fechaEjecucion->format('d/m/Y H:i') }} ·
                Estado inicial: {{ $m->estadoInicial ?? 'No registrado' }} ·
                Conclusión: {{ $m->conclusion ?? 'Sin conclusión' }}
            </div>
            <div class="activities">
                @php $algunaActividad = false; @endphp
                @foreach($actividades as $field => $label)
                    @if($m->$field)
                        @php $algunaActividad = true; @endphp
                        <span class="checkbox checked">X</span>{{ $label }}<br>
                    @else
                        <span class="checkbox"></span>{{ $label }}<br>
                    @endif
                @endforeach
                @if($m->act_otro)
                    <span class="checkbox checked">X</span>Otro: {{ $m->act_otro_detalle ?? 'Sin detalle' }}<br>
                @endif
                <br>
                <strong>Diagnóstico / observaciones:</strong>
                {{ $m->observaciones ?? 'Sin observaciones registradas.' }}
            </div>
        </div>
        @endforeach
    @endif

    <div class="footer">
        Reporte general generado el {{ now()->format('d/m/Y H:i') }} — Sistema HelpDesk
    </div>
</body>

</html>
