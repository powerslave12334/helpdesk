<?php

namespace App\Http\Requests;

use App\Models\IsoInventarioHerramienta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación del formulario FO-IT-04 (Inventario de herramienta).
 *
 * Validación de campos básicos de la herramienta. El acceso ya lo garantiza
 * el middleware permiso:iso9001 de la ruta, por lo que authorize() siempre es true.
 */
class InventarioHerramientaRequest extends FormRequest
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
            'codigo' => ['required', 'string', 'max:50', 'unique:tblIsoInventarioHerramienta,codigo'],
            'nombre' => ['required', 'string', 'max:150'],
            'marca' => ['required', 'string', 'max:100'],
            'modelo' => ['required', 'string', 'max:100'],
            'numeroSerie' => ['nullable', 'string', 'max:100'],
            'numeroInventario' => ['nullable', 'string', 'max:100'],
            'ubicacion' => ['required', 'string', 'max:150'],
            'estado' => ['required', 'string', Rule::in(array_keys(IsoInventarioHerramienta::ESTADOS))],
            'fechaAdquisicion' => ['nullable', 'date'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'codigo.required' => 'El código es obligatorio.',
            'codigo.unique' => 'El código ya existe en el inventario.',
            'codigo.max' => 'El código no puede superar 50 caracteres.',
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no puede superar 150 caracteres.',
            'marca.required' => 'La marca es obligatoria.',
            'marca.max' => 'La marca no puede superar 100 caracteres.',
            'modelo.required' => 'El modelo es obligatorio.',
            'modelo.max' => 'El modelo no puede superar 100 caracteres.',
            'numeroSerie.max' => 'El número de serie no puede superar 100 caracteres.',
            'numeroInventario.max' => 'El número de inventario no puede superar 100 caracteres.',
            'ubicacion.required' => 'La ubicación es obligatoria.',
            'ubicacion.max' => 'La ubicación no puede superar 150 caracteres.',
            'estado.required' => 'El estado es obligatorio.',
            'estado.in' => 'El estado debe coincidir con el catálogo del formato.',
            'fechaAdquisicion.date' => 'La fecha de adquisición no tiene un formato válido.',
        ];
    }
}
