<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación de la firma digital de FO-IT-01 (Responsiva ERP).
 *
 * Se envía al menos una firma (empleado o TI) como data-URI PNG generado
 * por el lienzo del modal (canvas.toDataURL('image/png')).
 * El acceso ya lo garantiza el middleware permiso:iso9001 de la ruta.
 */
class ResponsivaErpFirmarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $firma = ['nullable', 'string', 'max:2000000', 'regex:/^data:image\/png;base64,/'];

        return [
            'firmaEmpleado' => ['required_without:firmaTi', ...$firma],
            'firmaTi' => ['required_without:firmaEmpleado', ...$firma],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'firmaEmpleado.required_without' => 'Captura al menos una firma (empleado o TI).',
            'firmaTi.required_without' => 'Captura al menos una firma (empleado o TI).',
            'firmaEmpleado.regex' => 'La firma del empleado debe ser una imagen PNG válida.',
            'firmaTi.regex' => 'La firma de TI debe ser una imagen PNG válida.',
            'firmaEmpleado.max' => 'La firma del empleado supera el tamaño máximo permitido.',
            'firmaTi.max' => 'La firma de TI supera el tamaño máximo permitido.',
        ];
    }
}
