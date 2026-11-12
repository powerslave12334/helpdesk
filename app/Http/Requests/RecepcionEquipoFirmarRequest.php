<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación de la firma digital de FO-IT-03 (Recepción de equipo).
 *
 * Se envía al menos una firma (revisión o entrega) como data-URI PNG
 * generado por el lienzo del modal (canvas.toDataURL('image/png')).
 * El acceso ya lo garantiza el middleware permiso:iso9001 de la ruta.
 */
class RecepcionEquipoFirmarRequest extends FormRequest
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
            'firmaRevision' => ['required_without:firmaEntrega', ...$firma],
            'firmaEntrega' => ['required_without:firmaRevision', ...$firma],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'firmaRevision.required_without' => 'Captura al menos una firma (revisión o entrega).',
            'firmaEntrega.required_without' => 'Captura al menos una firma (revisión o entrega).',
            'firmaRevision.regex' => 'La firma de revisión debe ser una imagen PNG válida.',
            'firmaEntrega.regex' => 'La firma de entrega debe ser una imagen PNG válida.',
            'firmaRevision.max' => 'La firma de revisión supera el tamaño máximo permitido.',
            'firmaEntrega.max' => 'La firma de entrega supera el tamaño máximo permitido.',
        ];
    }
}
