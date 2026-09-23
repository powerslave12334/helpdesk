@extends('layouts.app')
@section('title', 'Editar Reporte de Mantenimiento - HelpDesk')

@section('styles')
<style>
    .form-section { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 20px; margin-bottom: 16px; }
    .form-section-title { font-size: 13px; font-weight: 700; color: var(--text); margin-bottom: 14px; padding-bottom: 8px; border-bottom: 2px solid var(--primary); display: flex; align-items: center; gap: 8px; }
    .form-section-title i { color: var(--primary); font-size: 14px; }

    .check-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 8px; }
    .check-item { display: flex; align-items: center; gap: 8px; padding: 8px 12px; background: var(--surface-strong); border: 1px solid var(--border); border-radius: var(--radius-sm); cursor: pointer; transition: all 0.15s; }
    .check-item:hover { border-color: var(--primary); background: var(--primary-soft); }
    .check-item input:checked + span { color: var(--primary); font-weight: 600; }
    .check-item input { accent-color: var(--primary); }

    .radio-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 8px; }
    .radio-item { display: flex; align-items: center; gap: 8px; padding: 10px 14px; background: var(--surface-strong); border: 1px solid var(--border); border-radius: var(--radius-sm); cursor: pointer; transition: all 0.15s; }
    .radio-item:hover { border-color: var(--primary); background: var(--primary-soft); }
    .radio-item:has(input:checked) { border-color: var(--primary); background: var(--primary-soft); }
    .radio-item input { accent-color: var(--primary); }
    .radio-item span { font-size: 13px; font-weight: 500; color: var(--text); }

    .check-table { width: 100%; border-collapse: collapse; }
    .check-table th { background: var(--surface-strong); padding: 8px 12px; border: 1px solid var(--border); text-align: left; font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 0.03em; }
    .check-table td { padding: 8px 12px; border: 1px solid var(--border); font-size: 13px; color: var(--text); }
    .check-table tr:hover td { background: var(--primary-soft); }
    .check-table select { border-radius: var(--radius-sm); font-size: 12px; padding: 4px 8px; min-width: 80px; }

    .equipo-info { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; margin-bottom: 16px; }
    .equipo-field { padding: 10px 14px; background: var(--surface-strong); border-radius: var(--radius-sm); }
    .equipo-field-label { font-size: 10px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 2px; }
    .equipo-field-value { font-size: 14px; font-weight: 600; color: var(--text); }

    .btn-generate { background: var(--primary); color: #fff; border: none; padding: 10px 24px; border-radius: var(--radius-sm); font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.15s; display: inline-flex; align-items: center; gap: 6px; }
    .btn-generate:hover { background: var(--primary-hover); transform: translateY(-1px); box-shadow: var(--shadow-md); }

    .btn-preview { background: var(--surface); color: var(--primary); border: 1px solid var(--primary); padding: 10px 24px; border-radius: var(--radius-sm); font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.15s; display: inline-flex; align-items: center; gap: 6px; }
    .btn-preview:hover { background: var(--primary-soft); transform: translateY(-1px); box-shadow: var(--shadow-md); }

    .otro-detalle { display: none; margin-top: 8px; }
    .otro-detalle.active { display: block; }

    .btn-cerrar { background: #dc2626; color: #fff; border: none; padding: 10px 24px; border-radius: var(--radius-sm); font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.15s; display: inline-flex; align-items: center; gap: 6px; }
    .btn-cerrar:hover { background: #b91c1c; transform: translateY(-1px); box-shadow: var(--shadow-md); }

    .cerrado-overlay { position: relative; }
    .cerrado-banner { background: rgba(220, 38, 38, 0.08); border: 1px solid rgba(220, 38, 38, 0.3); color: #dc2626; padding: 12px 16px; border-radius: var(--radius-sm); font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 8px; margin-bottom: 16px; }

    .readonly select, .readonly input[type="text"], .readonly textarea {
        pointer-events: none;
        opacity: 0.7;
        background: var(--surface-strong) !important;
    }
    .readonly input[type="radio"], .readonly input[type="checkbox"] {
        pointer-events: none;
        opacity: 1;
    }
    .readonly .check-item, .readonly .radio-item {
        pointer-events: none;
        cursor: default;
        opacity: 0.7;
    }
    .readonly .btn-preview, .readonly .btn-generate, .readonly .btn-cerrar {
        display: none !important;
    }
</style>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <a href="{{ route('inventario.mantenimientos.index') }}" class="text-decoration-none" style="color:var(--muted);font-size:13px">
            <i class="fas fa-arrow-left me-1"></i>Volver al calendario
        </a>
        <h4 class="mb-0 mt-1"><i class="fas fa-file-pdf me-2" style="color:var(--primary)"></i>Reporte de Mantenimiento</h4>
    </div>
</div>

<div class="equipo-info">
    <div class="equipo-field">
        <div class="equipo-field-label">Equipo</div>
        <div class="equipo-field-value">{{ $equipo->nombreEquipo ?? $equipo->marca }}</div>
    </div>
    <div class="equipo-field">
        <div class="equipo-field-label">Marca / Modelo</div>
        <div class="equipo-field-value">{{ $equipo->marca }} {{ $equipo->modelo }}</div>
    </div>
    <div class="equipo-field">
        <div class="equipo-field-label">Usuario Asignado</div>
        <div class="equipo-field-value">{{ $usuarioAsignado }}</div>
    </div>
    <div class="equipo-field">
        <div class="equipo-field-label">Técnico Actual</div>
        <div class="equipo-field-value">{{ $tecnico->nombre ?? 'N/A' }} {{ $tecnico->app ?? '' }}</div>
    </div>
    <div class="equipo-field">
        <div class="equipo-field-label">Tipo de Mantenimiento</div>
        <div class="equipo-field-value">{{ $mantenimiento->tipoMantenimiento }}</div>
    </div>
    <div class="equipo-field">
        <div class="equipo-field-label">Fecha Programada</div>
        <div class="equipo-field-value">{{ $mantenimiento->fechaProgramada->format('d/m/Y H:i') }}</div>
    </div>
</div>

<form id="reportForm" method="POST" action="{{ route('inventario.mantenimientos.guardar-reporte', $mantenimiento->id_Mantenimiento) }}">
    @csrf

    <!-- TÉCNICO ASIGNADO -->
    <div class="form-section">
        <div class="form-section-title"><i class="fas fa-user-cog"></i>Técnico Asignado</div>
        <select name="id_UsuarioTecnico" class="form-select" required style="border-radius:var(--radius-sm);max-width:350px">
            @foreach($tecnicos as $t)
            <option value="{{ $t->id_Usuario }}" {{ $mantenimiento->id_UsuarioTecnico == $t->id_Usuario ? 'selected' : '' }}>
                {{ $t->nombre }} {{ $t->app }}
            </option>
            @endforeach
        </select>
    </div>

    <!-- TIPO DE SOLICITUD -->
    <div class="form-section">
        <div class="form-section-title"><i class="fas fa-clipboard-list"></i>Tipo de Solicitud</div>
        <div class="radio-grid">
            <label class="radio-item">
                <input type="radio" name="tipoMantenimiento" value="Preventivo" {{ $mantenimiento->tipoMantenimiento == 'Preventivo' ? 'checked' : '' }}>
                <span>Mantenimiento Preventivo</span>
            </label>
            <label class="radio-item">
                <input type="radio" name="tipoMantenimiento" value="Correctivo" {{ $mantenimiento->tipoMantenimiento == 'Correctivo' ? 'checked' : '' }}>
                <span>Mantenimiento Correctivo</span>
            </label>
            <label class="radio-item">
                <input type="radio" name="tipoMantenimiento" value="Verificación" {{ $mantenimiento->tipoMantenimiento == 'Verificación' ? 'checked' : '' }}>
                <span>Verificación</span>
            </label>
        </div>
    </div>

    <!-- ESTADO INICIAL DEL EQUIPO -->
    <div class="form-section">
        <div class="form-section-title"><i class="fas fa-power-off"></i>Estado Inicial del Equipo</div>
        <div class="radio-grid">
            <label class="radio-item">
                <input type="radio" name="estadoInicial" value="Funcional" {{ ($mantenimiento->estadoInicial ?? '') == 'Funcional' ? 'checked' : '' }}>
                <span>Funcional</span>
            </label>
            <label class="radio-item">
                <input type="radio" name="estadoInicial" value="Funcional con fallas" {{ ($mantenimiento->estadoInicial ?? '') == 'Funcional con fallas' ? 'checked' : '' }}>
                <span>Funcional con fallas</span>
            </label>
            <label class="radio-item">
                <input type="radio" name="estadoInicial" value="No enciende" {{ ($mantenimiento->estadoInicial ?? '') == 'No enciende' ? 'checked' : '' }}>
                <span>No enciende</span>
            </label>
            <label class="radio-item">
                <input type="radio" name="estadoInicial" value="Otro" {{ ($mantenimiento->estadoInicial ?? '') == 'Otro' ? 'checked' : '' }}>
                <span>Otro</span>
            </label>
        </div>
    </div>

    <!-- PRUEBAS DE FUNCIONAMIENTO (HARDWARE) -->
    <div class="form-section">
        <div class="form-section-title"><i class="fas fa-microchip"></i>Pruebas de Funcionamiento (Hardware)</div>
        <table class="check-table">
            <thead>
                <tr>
                    <th style="width:50%">Detalle</th>
                    <th style="width:12.5%;text-align:center">Bueno (B)</th>
                    <th style="width:12.5%;text-align:center">Regular (R)</th>
                    <th style="width:12.5%;text-align:center">Malo (M)</th>
                    <th style="width:12.5%;text-align:center">N/A</th>
                </tr>
            </thead>
            <tbody>
                @php $hwFields = ['hw_encendido' => 'Encendido / Apagado', 'hw_pantalla' => 'Pantalla', 'hw_ventilacion' => 'Ventilación y ruidos', 'hw_dispositivos' => 'Dispositivos y periféricos']; @endphp
                @foreach($hwFields as $field => $label)
                <tr>
                    <td>{{ $label }}</td>
                    @foreach(['B' => 'Bueno', 'R' => 'Regular', 'M' => 'Malo', 'NA' => 'N/A'] as $val => $text)
                    <td style="text-align:center">
                        <input type="radio" name="{{ $field }}" value="{{ $val }}" {{ ($mantenimiento->$field ?? '') == $val ? 'checked' : '' }} style="accent-color:var(--primary)">
                    </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- VERIFICACIÓN DE SOFTWARE -->
    <div class="form-section">
        <div class="form-section-title"><i class="fas fa-laptop-code"></i>Verificación de Software</div>
        <table class="check-table">
            <thead>
                <tr>
                    <th style="width:50%">Detalle</th>
                    <th style="width:12.5%;text-align:center">Bueno (B)</th>
                    <th style="width:12.5%;text-align:center">Regular (R)</th>
                    <th style="width:12.5%;text-align:center">Malo (M)</th>
                    <th style="width:12.5%;text-align:center">N/A</th>
                </tr>
            </thead>
            <tbody>
                @php $swFields = ['sw_so' => 'S.O. Actualizado', 'sw_seguridad' => 'Actualizaciones de seguridad', 'sw_antivirus' => 'Antivirus Actualizado', 'sw_rendimiento' => 'Rendimiento general del sistema']; @endphp
                @foreach($swFields as $field => $label)
                <tr>
                    <td>{{ $label }}</td>
                    @foreach(['B' => 'Bueno', 'R' => 'Regular', 'M' => 'Malo', 'NA' => 'N/A'] as $val => $text)
                    <td style="text-align:center">
                        <input type="radio" name="{{ $field }}" value="{{ $val }}" {{ ($mantenimiento->$field ?? '') == $val ? 'checked' : '' }} style="accent-color:var(--primary)">
                    </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- ACTIVIDADES REALIZADAS -->
    <div class="form-section">
        <div class="form-section-title"><i class="fas fa-tasks"></i>Actividades Realizadas</div>
        <div class="check-grid">
            <label class="check-item">
                <input type="checkbox" name="act_limpieza" value="1" {{ ($mantenimiento->act_limpieza ?? false) ? 'checked' : '' }}>
                <span>Limpieza física interna y externa</span>
            </label>
            <label class="check-item">
                <input type="checkbox" name="act_revision_hw" value="1" {{ ($mantenimiento->act_revision_hw ?? false) ? 'checked' : '' }}>
                <span>Revisión de componentes de hardware</span>
            </label>
            <label class="check-item">
                <input type="checkbox" name="act_actualizacion_so" value="1" {{ ($mantenimiento->act_actualizacion_so ?? false) ? 'checked' : '' }}>
                <span>Actualización de sistema operativo</span>
            </label>
            <label class="check-item">
                <input type="checkbox" name="act_instalacion_sw" value="1" {{ ($mantenimiento->act_instalacion_sw ?? false) ? 'checked' : '' }}>
                <span>Instalación / actualización de software</span>
            </label>
            <label class="check-item">
                <input type="checkbox" name="act_parches" value="1" {{ ($mantenimiento->act_parches ?? false) ? 'checked' : '' }}>
                <span>Instalación de parches de seguridad</span>
            </label>
            <label class="check-item">
                <input type="checkbox" name="act_eliminacion" value="1" {{ ($mantenimiento->act_eliminacion ?? false) ? 'checked' : '' }}>
                <span>Eliminación de programas no autorizados</span>
            </label>
            <label class="check-item">
                <input type="checkbox" name="act_diagnostico" value="1" {{ ($mantenimiento->act_diagnostico ?? false) ? 'checked' : '' }}>
                <span>Diagnóstico y corrección de fallas</span>
            </label>
            <label class="check-item">
                <input type="checkbox" name="act_otro" value="1" {{ ($mantenimiento->act_otro ?? false) ? 'checked' : '' }} id="chkOtro">
                <span>Otro (especifique)</span>
            </label>
        </div>
        <div id="otroDetalle" class="otro-detalle {{ ($mantenimiento->act_otro ?? false) ? 'active' : '' }}">
            <input type="text" name="act_otro_detalle" class="form-control form-control-sm" placeholder="Especifique la actividad..." value="{{ $mantenimiento->act_otro_detalle ?? '' }}" style="border-radius:var(--radius-sm);max-width:400px">
        </div>
    </div>

    <!-- CONCLUSIÓN DEL SERVICIO -->
    <div class="form-section">
        <div class="form-section-title"><i class="fas fa-check-circle"></i>Conclusión del Servicio</div>
        <div class="radio-grid">
            <label class="radio-item">
                <input type="radio" name="conclusion" value="Equipo funcional" {{ ($mantenimiento->conclusion ?? '') == 'Equipo funcional' ? 'checked' : '' }}>
                <span>Equipo funcional</span>
            </label>
            <label class="radio-item">
                <input type="radio" name="conclusion" value="Equipo funcional con observaciones" {{ ($mantenimiento->conclusion ?? '') == 'Equipo funcional con observaciones' ? 'checked' : '' }}>
                <span>Equipo funcional con observaciones</span>
            </label>
            <label class="radio-item">
                <input type="radio" name="conclusion" value="Equipo pendiente de refacciones / seguimiento" {{ ($mantenimiento->conclusion ?? '') == 'Equipo pendiente de refacciones / seguimiento' ? 'checked' : '' }}>
                <span>Equipo pendiente de refacciones / seguimiento</span>
            </label>
        </div>
    </div>

    <!-- OBSERVACIONES -->
    <div class="form-section">
        <div class="form-section-title"><i class="fas fa-sticky-note"></i>Observaciones / Detalle del Diagnóstico</div>
        <textarea name="observaciones" class="form-control" rows="3" placeholder="Describa el diagnóstico y observaciones del mantenimiento..." style="border-radius:var(--radius-sm)">{{ $mantenimiento->observaciones ?? '' }}</textarea>
    </div>

    <!-- BOTONES -->
    <div class="d-flex justify-content-end gap-2 mt-3 mb-4" id="botonesForm">
        <a href="{{ route('inventario.mantenimientos.index') }}" class="btn btn-outline-secondary" style="border-radius:var(--radius-sm)">
            <i class="fas fa-times me-1"></i>Cancelar
        </a>
        <button type="submit" name="preview" value="1" class="btn-preview">
            <i class="fas fa-eye"></i>Vista Previa
        </button>
        <button type="submit" class="btn-generate">
            <i class="fas fa-download"></i>Descargar PDF
        </button>
        <button type="button" class="btn-cerrar" id="btnCerrarReporte">
            <i class="fas fa-lock"></i>Cerrar Reporte
        </button>
    </div>
</form>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const chkOtro = document.getElementById('chkOtro');
    const otroDetalle = document.getElementById('otroDetalle');

    chkOtro.addEventListener('change', function() {
        otroDetalle.classList.toggle('active', this.checked);
        if (!this.checked) {
            otroDetalle.querySelector('input').value = '';
        }
    });

    document.getElementById('reportForm').addEventListener('submit', function(e) {
        const estadoInicial = document.querySelector('input[name="estadoInicial"]:checked');
        const conclusion = document.querySelector('input[name="conclusion"]:checked');

        if (!estadoInicial) {
            e.preventDefault();
            alert('Selecciona el estado inicial del equipo');
            return;
        }
        if (!conclusion) {
            e.preventDefault();
            alert('Selecciona la conclusión del servicio');
            return;
        }
    });

    const reporteCerrado = {{ $mantenimiento->reporteCerrado ? 'true' : 'false' }};

    if (reporteCerrado) {
        document.getElementById('reportForm').classList.add('readonly');
        document.getElementById('botonesForm').style.display = 'none';

        document.querySelectorAll('#reportForm input, #reportForm select, #reportForm textarea').forEach(function(el) {
            if (!el.checked) {
                el.disabled = true;
            }
        });

        document.querySelectorAll('#reportForm .checkbox:not(.checked)').forEach(function(el) {
            el.style.visibility = 'hidden';
        });

        const banner = document.createElement('div');
        banner.className = 'cerrado-banner';
        banner.innerHTML = '<i class="fas fa-lock"></i>Este reporte está cerrado y no se puede editar.';
        document.getElementById('reportForm').prepend(banner);
    }

    document.getElementById('btnCerrarReporte').addEventListener('click', function() {
        if (!confirm('¿Está seguro de cerrar el reporte? Una vez cerrado no se podrá editar.')) {
            return;
        }

        fetch('{{ route("inventario.mantenimientos.cerrar-reporte", $mantenimiento->id_Mantenimiento) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        }).then(r => r.json()).then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            }
        });
    });
});
</script>
@endsection
