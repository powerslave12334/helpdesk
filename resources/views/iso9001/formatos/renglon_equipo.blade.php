{{--
    Renglón de la tabla de equipos del acta FO-IT-02.

    Se usa dos veces: en el bucle del formulario (restaurar = true, para
    re-pintar con old() tras un error de validación) y dentro del <template>
    que clona el JavaScript para agregar filas (restaurar = false).

    Variables:
      $indice    — número de renglón o "__I__" (placeholder del template).
      $fila      — valores por defecto del renglón.
      $restaurar — true para aplicar old() sobre $fila.
      $condiciones — catálogo de la columna "Condiciones".
--}}
@php
    $obtener = function (string $campo) use ($indice, $fila, $restaurar): mixed {
        $defecto = $fila[$campo] ?? null;

        return $restaurar ? old("equipos.{$indice}.{$campo}", $defecto) : $defecto;
    };
@endphp
<tr>
    <td>
        <input type="number" min="1" max="9999" name="equipos[{{ $indice }}][cantidad]"
               class="form-control form-control-sm" value="{{ $obtener('cantidad') }}" required>
    </td>
    <td>
        <input type="text" name="equipos[{{ $indice }}][descripcion]" maxlength="255"
               class="form-control form-control-sm" value="{{ $obtener('descripcion') }}"
               placeholder="Equipo entregado" required>
    </td>
    <td>
        <input type="text" name="equipos[{{ $indice }}][marca]" maxlength="100"
               class="form-control form-control-sm" value="{{ $obtener('marca') }}" placeholder="Opcional">
    </td>
    <td>
        <input type="text" name="equipos[{{ $indice }}][modelo]" maxlength="100"
               class="form-control form-control-sm" value="{{ $obtener('modelo') }}" placeholder="Opcional">
    </td>
    <td>
        <input type="text" name="equipos[{{ $indice }}][noSerie]" maxlength="100"
               class="form-control form-control-sm" value="{{ $obtener('noSerie') }}" placeholder="Opcional">
    </td>
    <td>
        <select name="equipos[{{ $indice }}][condiciones]" class="form-select form-select-sm" required>
            <option value="">Selecciona…</option>
            @foreach($condiciones as $opcion)
            <option value="{{ $opcion }}" @selected($obtener('condiciones') === $opcion)>{{ $opcion }}</option>
            @endforeach
        </select>
    </td>
    <td class="text-center">
        <button type="button" class="btn btn-sm btn-outline-danger" data-quitar title="Quitar renglón">
            <i class="fas fa-times"></i>
        </button>
    </td>
</tr>
