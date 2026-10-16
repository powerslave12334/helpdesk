@extends('layouts.app')
@section('title', 'Catálogos de Equipo - HelpDesk')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-database me-2"></i>Catálogos de Equipo</h4>
</div>

<ul class="nav nav-pills mb-3">
    @foreach($categorias as $slug => $c)
    <li class="nav-item">
        <a class="nav-link {{ $categoria === $slug ? 'active' : '' }}" href="{{ route('configuracion.catalogoEquipo.index', $slug) }}">{{ $c['nombre'] }}</a>
    </li>
    @endforeach
</ul>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">{{ $def['nombre'] }}</h5>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal"><i class="fas fa-plus me-1"></i>Nuevo registro</button>
        </div>
        <div data-table>
            <div class="table-toolbar">
                <div class="table-toolbar-left">
                    <div class="table-search">
                        <i class="fas fa-search"></i>
                        <input type="text" data-table-search placeholder="Buscar por nombre...">
                    </div>
                    <select class="table-filter" data-table-filter="estatus">
                        <option value="">Todos los estatus</option>
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            @if($categoria === 'modelo')<th>Marca</th>@endif
                            <th>Estatus</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody data-table-body>
                        @forelse($registros as $r)
                        <tr data-row>
                            <td data-field="id">{{ $r->{$r->getKeyName()} }}</td>
                            <td data-field="nombre">{{ $r->nombre }}</td>
                            @if($categoria === 'modelo')<td data-field="marca">{{ $r->marca->nombre ?? '—' }}</td>@endif
                            <td data-field="estatus"><span class="badge bg-{{ $r->activo ? 'success' : 'danger' }}">{{ $r->activo ? 'Activo' : 'Inactivo' }}</span></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary btn-edit"
                                        data-id="{{ $r->{$r->getKeyName()} }}"
                                        data-nombre="{{ $r->nombre }}"
                                        data-pid="{{ $categoria === 'modelo' ? ($r->id_Marca ?? '') : '' }}"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-outline-{{ $r->activo ? 'danger' : 'success' }} btn-toggle" data-id="{{ $r->{$r->getKeyName()} }}"><i class="fas fa-{{ $r->activo ? 'ban' : 'check' }}"></i></button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted">No hay registros</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="table-footer">
                <span class="table-info" data-table-info></span>
                <div class="pagination" data-table-pagination></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="formCat" action="{{ route('configuracion.catalogoEquipo.store', $categoria) }}">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">Nuevo registro</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if($errors->any())
                    <div class="alert alert-danger">
                        @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                    </div>
                    @endif
                    @if($categoria === 'modelo')
                    <div class="mb-3">
                        <label class="form-label">Marca *</label>
                        <select name="id_Marca" id="catPadre" class="form-select" required>
                            <option value="">Seleccionar...</option>
                            @foreach($padres as $p)
                            <option value="{{ $p->id_Marca }}">{{ $p->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label">Nombre *</label>
                        <input type="text" name="nombre" id="catNombre" class="form-control" value="{{ old('nombre') }}" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
const categoria = @json($categoria);
const formCat = document.getElementById('formCat');

document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', function() {
        formCat.action = '/configuracion/catalogo-equipo/' + categoria + '/' + this.dataset.id;
        document.getElementById('formMethod').value = 'PUT';
        document.getElementById('modalTitle').textContent = 'Editar registro';
        document.getElementById('catNombre').value = this.dataset.nombre;
        const padre = document.getElementById('catPadre');
        if (padre) padre.value = this.dataset.pid;
        new bootstrap.Modal(document.getElementById('createModal')).show();
    });
});

document.querySelectorAll('.btn-toggle').forEach(btn => {
    btn.addEventListener('click', function() {
        if (confirm('¿Cambiar estatus?')) {
            fetch(`/configuracion/catalogo-equipo/${categoria}/toggle/${this.dataset.id}`, {
                method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            }).then(() => location.reload());
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    @if($errors->any())
    new bootstrap.Modal(document.getElementById('createModal')).show();
    @endif
});
</script>
@endsection
