@extends('layouts.app')

@section('title', 'Monitoreo - HelpDesk')

@section('content')
<livewire:monitoreo-dashboard />

<!-- Modal: Nuevo objetivo -->


<!-- Modal: Editar objetivo -->
<div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="formEditar" method="POST" action="">
            @csrf @method('PUT')
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Editar objetivo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit-id">
                    <div class="mb-3">
                        <label class="form-label">Nombre *</label>
                        <input type="text" name="nombre" id="edit-nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo *</label>
                        <select name="tipo" id="edit-tipo" class="form-select" required>
                            <option value="servidor">Servidor</option>
                            <option value="servicio">Servicio</option>
                            <option value="sitio_web">Sitio web</option>
                            <option value="equipo">Equipo de cómputo</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Host o URL *</label>
                        <input type="url" name="host_o_url" id="edit-host" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Puerto</label>
                            <input type="number" name="puerto" id="edit-puerto" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Intervalo (min) *</label>
                            <input type="number" name="intervalo_minutos" id="edit-intervalo" class="form-control" min="1" max="1440" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Guardar cambios</button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const formEditar = document.getElementById('formEditar');

    // Abrir modal de editar
    document.querySelectorAll('.btn-editar').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit-id').value = this.dataset.id;
            document.getElementById('edit-nombre').value = this.dataset.nombre;
            document.getElementById('edit-tipo').value = this.dataset.tipo;
            document.getElementById('edit-host').value = this.dataset.host;
            document.getElementById('edit-puerto').value = this.dataset.puerto || '';
            document.getElementById('edit-intervalo').value = this.dataset.intervalo;
            formEditar.action = "{{ route('monitoreo.update', 'ID') }}".replace('ID', this.dataset.id);
            new bootstrap.Modal(document.getElementById('modalEditar')).show();
        });
    });
});
</script>
@endsection
