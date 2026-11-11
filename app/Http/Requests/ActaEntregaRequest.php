<?php

namespace App\Http\Requests;

use App\Models\IsoActaEntrega;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación del formulario FO-IT-02 (Acta de entrega).
 *
 * La tabla de equipos llega como arreglo de renglones dinámicos
 * (equipos[i][...] con al menos un renglón); las condiciones se validan
 * contra el catálogo del formato. El acceso ya lo garantiza el middleware
 * permiso:iso9001 de la ruta, por lo que authorize() siempre es true.
 */
class ActaEntregaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación de los datos generales y de cada renglón.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'fecha' => ['required', 'date'],
            'entregadoPor' => ['required', 'string', 'max:150'],
            'recibidoPor' => ['required', 'string', 'max:150'],
            'equipos' => ['required', 'array', 'min:1', 'max:100'],
            'equipos.*.cantidad' => ['required', 'integer', 'min:1', 'max:9999'],
            'equipos.*.descripcion' => ['required', 'string', 'max:255'],
            'equipos.*.marca' => ['nullable', 'string', 'max:100'],
            'equipos.*.modelo' => ['nullable', 'string', 'max:100'],
            'equipos.*.noSerie' => ['nullable', 'string', 'max:100'],
            'equipos.*.condiciones' => ['required', 'string', Rule::in(IsoActaEntrega::CONDICIONES)],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'fecha.required' => 'La fecha es obligatoria.',
            'fecha.date' => 'La fecha no tiene un formato válido.',
            'entregadoPor.required' => 'El nombre de quien entrega es obligatorio.',
            'entregadoPor.max' => 'El nombre de quien entrega no puede superar 150 caracteres.',
            'recibidoPor.required' => 'El nombre de quien recibe es obligatorio.',
            'recibidoPor.max' => 'El nombre de quien recibe no puede superar 150 caracteres.',
            'equipos.required' => 'Agrega al menos un equipo al acta.',
            'equipos.min' => 'Agrega al menos un equipo al acta.',
            'equipos.max' => 'El acta no puede superar 100 renglones de equipos.',
            'equipos.*.cantidad.required' => 'La cantidad es obligatoria en cada renglón.',
            'equipos.*.cantidad.integer' => 'La cantidad debe ser un número entero.',
            'equipos.*.cantidad.min' => 'La cantidad debe ser al menos 1.',
            'equipos.*.cantidad.max' => 'La cantidad no puede superar 9999.',
            'equipos.*.descripcion.required' => 'La descripción es obligatoria en cada renglón.',
            'equipos.*.descripcion.max' => 'La descripción no puede superar 255 caracteres.',
            'equipos.*.marca.max' => 'La marca no puede superar 100 caracteres.',
            'equipos.*.modelo.max' => 'El modelo no puede superar 100 caracteres.',
            'equipos.*.noSerie.max' => 'El no. de serie no puede superar 100 caracteres.',
            'equipos.*.condiciones.required' => 'Selecciona las condiciones en cada renglón.',
            'equipos.*.condiciones.in' => 'Las condiciones deben coincidir con el catálogo del formato.',
        ];
    }
}
