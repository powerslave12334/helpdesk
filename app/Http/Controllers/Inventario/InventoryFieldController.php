<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use App\Models\InventoryField;
use App\Models\InventoryFieldSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Catálogo maestro de campos del inventario.
 *
 * Permite DEFINIR los campos primero (esta pantalla) y luego ASIGNARLOS
 * a un Tipo de Equipo desde inventario.tipoEquipo.campos.
 */
class InventoryFieldController extends Controller
{
    /** Tipos de dato aceptados para un campo dinámico. */
    public const TIPOS = [
        'text' => 'Texto',
        'textarea' => 'Texto largo',
        'number' => 'Número entero',
        'decimal' => 'Número decimal',
        'date' => 'Fecha',
        'datetime' => 'Fecha y hora',
        'boolean' => 'Sí / No',
        'select' => 'Selección (opciones)',
        'tags' => 'Etiquetas múltiples',
    ];

    public function index()
    {
        $secciones = InventoryFieldSection::with(['fields' => fn ($q) => $q->activos()])
            ->orderBy('order')
            ->get();

        $tipos = self::TIPOS;

        // Campos ya asignados a algún tipo, para avisar antes de desactivar/eliminar.
        $enUso = DB::table('tblTipoEquipoCampoConfig')
            ->where('activo', true)
            ->pluck('campo')
            ->flip();

        return view('inventario.campos_catalogo', compact('secciones', 'tipos', 'enUso'));
    }

    public function store(Request $request)
    {
        $data = $this->validar($request);

        DB::transaction(function () use ($data) {
            $maxOrder = InventoryField::where('section_id', $data['section_id'])->max('order');
            /**
             * Un campo creado por el usuario no tiene columna real en tblEquipo,
             * así que su valor SIEMPRE vive en el JSON attributes.
             * (Los de origen 'column' solo los genera el seeder inicial.)
             */
            InventoryField::create($data + [
                'source' => InventoryField::SOURCE_CUSTOM,
                'order' => ($maxOrder ?? 0) + 1,
                'is_active' => true,
            ]);
        });

        return back()->with('success', 'Campo "'.$data['label'].'" creado. Ya puede asignarlo a un tipo de equipo.');
    }

    public function update(Request $request, $id)
    {
        $campo = InventoryField::findOrFail($id);
        $data = $this->validar($request, $id);

        $campo->update($data);

        return back()->with('success', 'Campo actualizado correctamente');
    }

    public function toggle($id)
    {
        $campo = InventoryField::findOrFail($id);
        $campo->is_active = ! $campo->is_active;
        $campo->save();

        return back()->with('success', $campo->is_active ? 'Campo activado' : 'Campo desactivado');
    }

    public function destroy($id)
    {
        $campo = InventoryField::findOrFail($id);

        $enUso = DB::table('tblTipoEquipoCampoConfig')
            ->where('campo', $campo->name)
            ->where('activo', true)
            ->exists();

        if ($enUso) {
            return back()->with('error', 'No se puede eliminar: el campo está asignado a uno o más tipos de equipo. Desactívelo en su lugar.');
        }

        $campo->delete();

        return back()->with('success', 'Campo eliminado del catálogo');
    }

    /**
     * Reglas de validación comunes a crear/editar.
     * Solo los campos 'custom' pueden cambiar de nombre: los de origen
     * 'column' apuntan a una columna real de tblEquipo y renombrarlos
     * rompería el guardado de los equipos existentes.
     */
    private function validar(Request $request, ?int $id = null): array
    {
        $campo = $id ? InventoryField::findOrFail($id) : null;

        $reglas = [
            'section_id' => ['required', 'integer', Rule::exists('inventory_field_sections', 'id')],
            'label' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(array_keys(self::TIPOS))],
            'help' => ['nullable', 'string', 'max:255'],
        ];

        if ($campo && ! $campo->isCustom()) {
            // Nombre fijo: solo se conserva, no se valida ni se actualiza.
            $reglas['name'] = ['nullable', 'string'];
        } else {
            $reglas['name'] = ['required', 'string', 'max:60', 'regex:/^[a-z][a-zA-Z0-9_]*$/'];
            $reglas['name'][] = Rule::unique('inventory_fields', 'name')->ignore($id);
        }

        $data = $request->validate($reglas);

        // Los tipos de datos numéricos/fecha no admiten opciones.
        $opciones = $request->input('opciones');
        if (in_array($data['type'], ['select', 'tags'], true) && is_string($opciones)) {
            $data['validation_rules'] = [
                'opciones' => array_values(array_filter(array_map('trim', explode(',', $opciones)))),
            ];
        } else {
            $data['validation_rules'] = null;
        }

        if ($campo && ! $campo->isCustom()) {
            unset($data['name']); // nunca renombrar un campo de columna real
        }

        return $data;
    }
}
