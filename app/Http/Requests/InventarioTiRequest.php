<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación del formulario FO-IT-05 (Inventario de TI) sobre tblEquipo.
 *
 * El acceso ya lo garantiza el middleware permiso:iso9001 de la ruta,
 * por lo que authorize() siempre es true.
 */
class InventarioTiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación de los datos del formato.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'usuario_id' => ['required', 'integer', 'exists:tblUsuario,id_Usuario'],
            'departamento' => ['nullable', 'string', 'max:100'],
            'ubicacion' => ['required', 'string', 'max:150'],
            'tipoEquipo' => ['required', 'integer', 'exists:tblTipoEquipo,id_TipoEquipo'],
            'noSerie' => ['required', 'string', 'max:100'],
            'marca' => ['required', 'string', 'max:100'],
            'modelo' => ['required', 'string', 'max:100'],
            'condicion' => ['required', 'integer', 'exists:tblEstatus,id_Estatus'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'usuario_id.required' => 'El usuario es obligatorio.',
            'usuario_id.integer' => 'El usuario debe ser un valor válido.',
            'usuario_id.exists' => 'El usuario seleccionado no existe.',
            'departamento.max' => 'El departamento no puede superar 100 caracteres.',
            'ubicacion.required' => 'La ubicación es obligatoria.',
            'ubicacion.max' => 'La ubicación no puede superar 150 caracteres.',
            'tipoEquipo.required' => 'El tipo de equipo es obligatorio.',
            'tipoEquipo.integer' => 'El tipo de equipo debe ser un valor válido.',
            'tipoEquipo.exists' => 'El tipo de equipo seleccionado no existe.',
            'noSerie.required' => 'El número de serie es obligatorio.',
            'noSerie.max' => 'El número de serie no puede superar 100 caracteres.',
            'marca.required' => 'La marca es obligatoria.',
            'marca.max' => 'La marca no puede superar 100 caracteres.',
            'modelo.required' => 'El modelo es obligatorio.',
            'modelo.max' => 'El modelo no puede superar 100 caracteres.',
            'condicion.required' => 'La condición es obligatoria.',
            'condicion.integer' => 'La condición debe ser un valor válido.',
            'condicion.exists' => 'La condición seleccionada no existe.',
        ];
    }
}
