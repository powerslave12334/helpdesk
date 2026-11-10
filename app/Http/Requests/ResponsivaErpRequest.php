<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación del formulario FO-IT-01 (Responsiva ERP).
 *
 * El acceso ya lo garantiza el middleware permiso:iso9001 de la ruta, por lo
 * que authorize() siempre es true.
 */
class ResponsivaErpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación de los 6 campos del formato.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'fecha' => ['required', 'date'],
            'nombreEmpleado' => ['required', 'string', 'max:150'],
            'puesto' => ['required', 'string', 'max:100'],
            'correo' => ['required', 'string', 'email', 'max:150'],
            'usuarioErp' => ['required', 'string', 'max:100'],
            'contrasenaErp' => ['required', 'string', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'fecha.required' => 'La fecha es obligatoria.',
            'fecha.date' => 'La fecha no tiene un formato válido.',
            'nombreEmpleado.required' => 'El nombre del empleado responsable es obligatorio.',
            'nombreEmpleado.max' => 'El nombre no puede superar 150 caracteres.',
            'puesto.required' => 'El puesto es obligatorio.',
            'puesto.max' => 'El puesto no puede superar 100 caracteres.',
            'correo.required' => 'El correo es obligatorio.',
            'correo.email' => 'El correo no tiene un formato válido.',
            'correo.max' => 'El correo no puede superar 150 caracteres.',
            'usuarioErp.required' => 'El usuario del ERP es obligatorio.',
            'usuarioErp.max' => 'El usuario del ERP no puede superar 100 caracteres.',
            'contrasenaErp.required' => 'La contraseña del ERP es obligatoria.',
            'contrasenaErp.max' => 'La contraseña no puede superar 255 caracteres.',
        ];
    }
}
