@extends('layouts.app')
@section('title', $formato['clave'] . ' - ' . $formato['nombre'] . ' - ISO 9001')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0">
            <i class="{{ $formato['icono'] ?? 'fas fa-file' }} me-2"></i>{{ $formato['nombre'] }}
            <span class="badge bg-primary ms-2">{{ $formato['clave'] }}</span>
        </h4>
        <p class="text-muted mb-0">Documentos generados de este formato</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('iso9001.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i>Volver
        </a>
        <a href="{{ route('iso9001.verificar') }}" class="btn btn-outline-secondary" title="Verificar la huella de un documento">
            <i class="fas fa-fingerprint me-1"></i>Verificar
        </a>
        <a href="{{ route('iso9001.create', ['clave' => $formato['clave']]) }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Nuevo documento
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Fecha</th>
                        <th>Entregado por</th>
                        <th>Recibido por</th>
                        <th>Equipos</th>
                        <th>Creado por</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($documentos as $documento)
                    <tr>
                        <td><strong>{{ $documento->folio }}</strong></td>
                        <td>{{ $documento->fecha->format('d/m/Y') }}</td>
                        <td>{{ $documento->entregadoPor }}</td>
                        <td>{{ $documento->recibidoPor }}</td>
                        <td>
                            <i class="fas fa-laptop me-1 text-muted"></i>{{ $documento->equipos->count() }}
                        </td>
                        <td>
                            {{ $documento->creador?->nombreCompleto() ?? '—' }}
                            <div class="text-muted" style="font-size: 11px;">{{ $documento->fechaCreacion->format('d/m/Y H:i') }}</div>
                        </td>
                        <td>
                            @if($documento->estaFirmado())
                            <span class="badge bg-success"
                                  title="Entrega: {{ $documento->fechaFirmaEntrega?->format('d/m/Y H:i') ?? 'sin firmar' }} · Recepción: {{ $documento->fechaFirmaRecepcion?->format('d/m/Y H:i') ?? 'sin firmar' }}">
                                <i class="fas fa-signature me-1"></i>Firmado
                            </span>
                            @else
                            <span class="badge bg-secondary">Pendiente</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="btn-group">
                                <a href="{{ route('iso9001.pdf', ['clave' => $formato['clave'], 'id' => $documento->id_ActaEntrega]) }}"
                                   target="_blank" class="btn btn-sm btn-outline-primary" title="Ver PDF">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('iso9001.pdf', ['clave' => $formato['clave'], 'id' => $documento->id_ActaEntrega]) . '?descargar=1' }}"
                                   class="btn btn-sm btn-outline-success" title="Descargar PDF">
                                    <i class="fas fa-download"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-warning"
                                        title="Firmar documento"
                                        onclick="abrirFirma({{ $documento->id_ActaEntrega }}, '{{ $documento->folio }}')">
                                    <i class="fas fa-signature"></i>
                                </button>
                                @if($documento->estaFirmado())
                                <button type="button" class="btn btn-sm btn-outline-secondary" disabled
                                        title="Documento firmado: no se puede eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                                @else
                                <form action="{{ route('iso9001.destroy', ['clave' => $formato['clave'], 'id' => $documento->id_ActaEntrega]) }}"
                                      method="POST" class="d-inline"
                                      onsubmit="return confirm('¿Eliminar el documento {{ $documento->folio }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fas fa-hand-holding fa-2x mb-2 d-block"></i>
                            Aún no se han generado documentos <strong>{{ $formato['clave'] }}</strong>.
                            Presiona <strong>"Nuevo documento"</strong> para crear el primero.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal de firma digital: lienzo por firmante (entrega ↔ recepción). --}}
<div class="modal fade" id="modalFirma" tabindex="-1" aria-labelledby="modalFirmaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formFirma" method="POST" action="">
                @csrf
                <input type="hidden" name="firmaEntrega" value="">
                <input type="hidden" name="firmaRecepcion" value="">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalFirmaLabel">
                        <i class="fas fa-signature me-2"></i>Firmar documento
                        <span class="badge bg-primary ms-2" id="firmaFolio"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Entregado por</label>
                            <canvas id="lienzoEntrega" class="lienzo-firma"></canvas>
                            <div class="d-flex justify-content-between align-items-center mt-1">
                                <small class="text-muted">Dibuja con el mouse o dedo</small>
                                <button type="button" class="btn btn-sm btn-outline-secondary btnLimpiar" data-campo="firmaEntrega">
                                    <i class="fas fa-eraser me-1"></i>Limpiar
                                </button>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Recibido por</label>
                            <canvas id="lienzoRecepcion" class="lienzo-firma"></canvas>
                            <div class="d-flex justify-content-between align-items-center mt-1">
                                <small class="text-muted">Dibuja con el mouse o dedo</small>
                                <button type="button" class="btn btn-sm btn-outline-secondary btnLimpiar" data-campo="firmaRecepcion">
                                    <i class="fas fa-eraser me-1"></i>Limpiar
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info mt-4 mb-0" style="font-size: 13px;">
                        <i class="fas fa-info-circle me-1"></i>
                        Se guardará <strong>solo lo que esté capturado</strong>: si dejas un lienzo vacío,
                        la firma existente de ese firmante se conserva sin cambios.
                        Al firmar, el documento queda bloqueado para eliminarse.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check me-1"></i>Guardar firma
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .lienzo-firma {
        width: 100%;
        height: 150px;
        border: 1px dashed var(--border);
        border-radius: var(--radius-sm);
        background-color: #fff;
        touch-action: none;
    }
</style>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/signature_pad@5.1.4/dist/signature_pad.umd.min.js"></script>
<script>
    (function () {
        const opciones = { backgroundColor: 'rgba(255,255,255,0)', minWidth: 0.5, maxWidth: 2.5 };
        const lienzos = {
            firmaEntrega: new SignaturePad(document.getElementById('lienzoEntrega'), opciones),
            firmaRecepcion: new SignaturePad(document.getElementById('lienzoRecepcion'), opciones),
        };
        const modal = new bootstrap.Modal(document.getElementById('modalFirma'));

        // Al abrir: ajusta el lienzo al tamaño real del modal y lo limpia.
        document.getElementById('modalFirma').addEventListener('shown.bs.modal', function () {
            Object.values(lienzos).forEach(function (lienzo) {
                const canvas = lienzo.canvas;
                canvas.width = canvas.clientWidth;
                canvas.height = canvas.clientHeight;
                lienzo.clear();
            });
        });

        document.querySelectorAll('.btnLimpiar').forEach(function (boton) {
            boton.addEventListener('click', function () {
                lienzos[boton.dataset.campo].clear();
            });
        });

        window.abrirFirma = function (id, folio) {
            document.getElementById('formFirma').action =
                '{{ route('iso9001.firmar', ['clave' => $formato['clave'], 'id' => '__ID__']) }}'.replace('__ID__', id);
            document.getElementById('firmaFolio').textContent = folio;
            modal.show();
        };

        document.getElementById('formFirma').addEventListener('submit', function (evento) {
            let algunaFirma = false;

            Object.keys(lienzos).forEach(function (campo) {
                const lienzo = lienzos[campo];
                const input = this.elements[campo];

                if (lienzo.isEmpty()) {
                    input.value = '';
                } else {
                    input.value = lienzo.toDataURL('image/png');
                    algunaFirma = true;
                }
            }, this);

            if (!algunaFirma) {
                evento.preventDefault();
                Swal.fire('Captura una firma', 'Dibuja al menos una firma antes de guardar.', 'warning');
            }
        });
    })();
</script>
@endsection
