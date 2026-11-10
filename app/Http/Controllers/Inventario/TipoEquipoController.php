<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use App\Models\InventoryField;
use App\Models\InventoryFieldSection;
use App\Models\TipoEquipo;
use App\Models\TipoEquipoCampoConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TipoEquipoController extends Controller
{
    public function index()
    {
        $tipos = TipoEquipo::with('camposConfig')->get();

        return view('inventario.tipo_equipo', compact('tipos'));
    }

    public function store(Request $request)
    {
        $request->validate(['descripcion' => 'required|string|max:100|unique:tblTipoEquipo,descripcion']);
        $tipo = TipoEquipo::create([
            'descripcion' => $request->descripcion,
            'fechaAlta' => now(),
            'activo' => true,
        ]);

        // Asignar campos mínimos iniciales por defecto (Identificación y Observaciones)
        $camposIniciales = ['marca', 'modelo', 'numeroSerie', 'nombreEquipo', 'ubicacion', 'id_Oficinas', 'observaciones'];
        $orden = 1;
        $now = now();
        $configInicial = [];
        foreach ($camposIniciales as $c) {
            $configInicial[] = [
                'id_TipoEquipo' => $tipo->id_TipoEquipo,
                'campo' => $c,
                'es_requerido' => in_array($c, ['marca', 'modelo'], true),
                'orden' => $orden++,
                'activo' => true,
                'fechaAlta' => $now,
            ];
        }
        TipoEquipoCampoConfig::insert($configInicial);

        return redirect()->route('inventario.tipoEquipo.campos', $tipo->id_TipoEquipo)
            ->with('success', 'Tipo de equipo creado. Personaliza los campos que se solicitarán a continuación.');
    }

    public function update(Request $request, $id)
    {
        $request->validate(['descripcion' => 'required|string|max:100|unique:tblTipoEquipo,descripcion,'.$id.',id_TipoEquipo']);
        TipoEquipo::findOrFail($id)->update(['descripcion' => $request->descripcion]);

        return redirect()->route('inventario.tipoEquipo.index')->with('success', 'Tipo de equipo actualizado correctamente');
    }

    public function toggleActivo($id)
    {
        $tipo = TipoEquipo::findOrFail($id);
        $tipo->activo = ! $tipo->activo;
        $tipo->save();

        return back()->with('success', $tipo->activo ? 'Tipo activado' : 'Tipo desactivado');
    }

    /**
     * Muestra el panel interactivo para personalizar qué campos
     * se asignan a este tipo de equipo. El catálogo viene de la BD
     * (definido antes en inventario.campos.index).
     */
    public function personalizarCampos($id)
    {
        $tipo = TipoEquipo::with('camposConfig')->findOrFail($id);

        $seccionesCatalogo = InventoryFieldSection::with(['fields' => fn ($q) => $q->activos()])
            ->orderBy('order')
            ->get()
            ->mapWithKeys(fn ($sec) => [
                $sec->key => [
                    'titulo' => $sec->title,
                    'icono' => $sec->icon ?: 'fas fa-folder',
                    'descripcion' => $sec->description,
                    'campos' => $sec->fields->mapWithKeys(fn ($f) => [
                        $f->name => [
                            'etiqueta' => $f->label,
                            'tipo' => $f->type,
                            'ayuda' => $f->help,
                            'es_custom' => $f->isCustom(),
                        ],
                    ])->all(),
                ],
            ]);

        // Mapa de campos asignados actualmente: campo => ['activo' => bool, 'es_requerido' => bool]
        $asignados = $tipo->camposConfig->keyBy('campo');

        return view('inventario.tipo_equipo_campos', compact('tipo', 'seccionesCatalogo', 'asignados'));
    }

    /**
     * Guarda la selección de campos activados y requeridos para el tipo de equipo.
     * Acepta tanto campos de columna como campos personalizados del catálogo.
     */
    public function guardarCampos(Request $request, $id)
    {
        $tipo = TipoEquipo::findOrFail($id);

        // Catálogo válido: todo campo activo definido en inventario.campos
        $camposValidos = InventoryField::activos()->pluck('name')->all();

        $camposActivos = $request->input('campos', []); // array de strings
        $camposRequeridos = $request->input('requeridos', []); // array de strings

        // Solo conservar los que existen en el catálogo y estén activos
        $camposActivos = array_values(array_intersect($camposActivos, $camposValidos));

        DB::beginTransaction();
        try {
            // Eliminar configuración anterior
            TipoEquipoCampoConfig::where('id_TipoEquipo', $tipo->id_TipoEquipo)->delete();

            $nuevosRegistros = [];
            $orden = 1;
            $now = now();

            foreach ($camposActivos as $campo) {
                $nuevosRegistros[] = [
                    'id_TipoEquipo' => $tipo->id_TipoEquipo,
                    'campo' => $campo,
                    'es_requerido' => in_array($campo, $camposRequeridos, true),
                    'orden' => $orden++,
                    'activo' => true,
                    'fechaAlta' => $now,
                ];
            }

            if (! empty($nuevosRegistros)) {
                TipoEquipoCampoConfig::insert($nuevosRegistros);
            }

            DB::commit();

            return redirect()->route('inventario.tipoEquipo.campos', $tipo->id_TipoEquipo)
                ->with('success', 'Campos asignados a "'.$tipo->descripcion.'" actualizados correctamente');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Error al guardar configuración de campos: '.$e->getMessage());
        }
    }
}
