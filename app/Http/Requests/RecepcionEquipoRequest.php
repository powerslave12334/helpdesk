<?php

namespace App\Http\Requests;

use App\Models\IsoRecepcionEquipo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación del formulario FO-IT-03 (Recepción de equipo).
 *
 * Los grupos de checkboxes llegan como: selección única en un campo de
 * texto (motivoEntrega, aplicacionGastos) y selección múltiple en arreglos
 * (tipoActivo[], accesorios[]), validados contra los catálogos del formato.
 * El acceso ya lo garantiza el middleware permiso:iso9001 de la ruta, por
 * lo que authorize() siempre es true.
 */
class RecepcionEquipoRequest extends FormRequest
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
            'fecha' => ['required', 'date'],
            'motivoEntrega' => ['required', 'string', Rule::in(IsoRecepcionEquipo::MOTIVOS)],
            'tipoActivo' => ['required', 'array', 'min:1', 'max:10'],
            'tipoActivo.*' => ['string', Rule::in(IsoRecepcionEquipo::TIPOS_ACTIVO)],
            'marca' => ['required', 'string', 'max:100'],
            'modelo' => ['required', 'string', 'max:100'],
            'noSerie' => ['required', 'string', 'max:100'],
            'imei' => ['nullable', 'string', 'max:100'],
            'numeroTelefonico' => ['nullable', 'string', 'max:50'],
            'accesorios' => ['required', 'array', 'min:1', 'max:10'],
            'accesorios.*' => ['string', Rule::in(IsoRecepcionEquipo::ACCESORIOS)],
            'descripcionEntrega' => ['required', 'string', 'max:500'],
            'aplicacionGastos' => ['required', 'string', Rule::in(IsoRecepcionEquipo::APLICACION_GASTOS)],
            'gastosReparacion' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'revisadoPor' => ['required', 'string', 'max:150'],
            'entregadoPor' => ['required', 'string', 'max:150'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'fecha.required' => 'La fecha es obligatoria.',
            'fecha.date' => 'La fecha no tiene un formato válido.',
            'motivoEntrega.required' => 'Selecciona el motivo de la entrega.',
            'motivoEntrega.in' => 'El motivo debe coincidir con el catálogo del formato.',
            'tipoActivo.required' => 'Selecciona al menos un tipo de activo.',
            'tipoActivo.min' => 'Selecciona al menos un tipo de activo.',
            'tipoActivo.*.in' => 'El tipo de activo debe coincidir con el catálogo del formato.',
            'marca.required' => 'La marca es obligatoria.',
            'marca.max' => 'La marca no puede superar 100 caracteres.',
            'modelo.required' => 'El modelo es obligatorio.',
            'modelo.max' => 'El modelo no puede superar 100 caracteres.',
            'noSerie.required' => 'El no. de serie es obligatorio.',
            'noSerie.max' => 'El no. de serie no puede superar 100 caracteres.',
            'imei.max' => 'El IMEI no puede superar 100 caracteres.',
            'numeroTelefonico.max' => 'El número telefónico no puede superar 50 caracteres.',
            'accesorios.required' => 'Selecciona al menos un accesorio (o "Sin Accesorios").',
            'accesorios.min' => 'Selecciona al menos un accesorio (o "Sin Accesorios").',
            'accesorios.*.in' => 'El accesorio debe coincidir con el catálogo del formato.',
            'descripcionEntrega.required' => 'La descripción de la entrega es obligatoria.',
            'descripcionEntrega.max' => 'La descripción no puede superar 500 caracteres.',
            'aplicacionGastos.required' => 'Selecciona la aplicación de gastos de reparación.',
            'aplicacionGastos.in' => 'La aplicación de gastos debe coincidir con el catálogo del formato.',
            'gastosReparacion.required' => 'Los gastos de reparación son obligatorios (usa 0 si no aplica).',
            'gastosReparacion.numeric' => 'Los gastos de reparación deben ser un número.',
            'gastosReparacion.min' => 'Los gastos de reparación no pueden ser negativos.',
            'revisadoPor.required' => 'El nombre de quien realiza la revisión es obligatorio.',
            'revisadoPor.max' => 'El nombre de quien realiza la revisión no puede superar 150 caracteres.',
            'entregadoPor.required' => 'El nombre de quien entrega el activo es obligatorio.',
            'entregadoPor.max' => 'El nombre de quien entrega el activo no puede superar 150 caracteres.',
        ];
    }
}
