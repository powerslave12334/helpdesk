<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación de la firma digital de FO-IT-02 (Acta de entrega).
 *
 * Se envía al menos una firma (entrega o recepción) como data-URI PNG
 * generado por el lienzo del modal (canvas.toDataURL('image/png')).
 * El acceso ya lo garantiza el middleware permiso:iso9001 de la ruta.
 */
class ActaEntregaFirmarRequest extends FormRequest
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
            'firmaEntrega' => ['required_without:firmaRecepcion', ...$firma],
            'firmaRecepcion' => ['required_without:firmaEntrega', ...$firma],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'firmaEntrega.required_without' => 'Captura al menos una firma (entrega o recepción).',
            'firmaRecepcion.required_without' => 'Captura al menos una firma (entrega o recepción).',
            'firmaEntrega.regex' => 'La firma de entrega debe ser una imagen PNG válida.',
            'firmaRecepcion.regex' => 'La firma de recepción debe ser una imagen PNG válida.',
            'firmaEntrega.max' => 'La firma de entrega supera el tamaño máximo permitido.',
            'firmaRecepcion.max' => 'La firma de recepción supera el tamaño máximo permitido.',
        ];
    }
}
