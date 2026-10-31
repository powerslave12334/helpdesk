<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\CatMarca;
use App\Models\CatModelo;
use App\Models\CatProcesador;
use App\Models\CatSistemaOperativo;
use App\Models\CatTipoAlmacenamiento;
use App\Models\CatTipoTeclado;
use App\Models\CatConectividad;
use App\Models\CatTipoImpresora;
use App\Models\CatTipoCamara;
use App\Models\CatTipoGps;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CatalogoEquipoController extends Controller
{
    private const CATEGORIAS = [
        'marca' => ['nombre' => 'Marcas', 'clase' => CatMarca::class, 'tabla' => 'tblCatMarca', 'pk' => 'id_Marca'],
        'modelo' => ['nombre' => 'Modelos', 'clase' => CatModelo::class, 'tabla' => 'tblCatModelo', 'pk' => 'id_Modelo'],
        'procesador' => ['nombre' => 'Procesadores', 'clase' => CatProcesador::class, 'tabla' => 'tblCatProcesador', 'pk' => 'id_Procesador'],
        'sistema-operativo' => ['nombre' => 'Sistemas Operativos', 'clase' => CatSistemaOperativo::class, 'tabla' => 'tblCatSistemaOperativo', 'pk' => 'id_SistemaOperativo'],
        'almacenamiento' => ['nombre' => 'Tipos de Almacenamiento', 'clase' => CatTipoAlmacenamiento::class, 'tabla' => 'tblCatTipoAlmacenamiento', 'pk' => 'id_TipoAlmacenamiento'],
        'tipo-teclado' => ['nombre' => 'Tipos de Teclado', 'clase' => CatTipoTeclado::class, 'tabla' => 'tblCatTipoTeclado', 'pk' => 'id_TipoTeclado'],
        'conectividad' => ['nombre' => 'Conectividad', 'clase' => CatConectividad::class, 'tabla' => 'tblCatConectividad', 'pk' => 'id_Conectividad'],
        'tipo-impresora' => ['nombre' => 'Tipos de Impresora', 'clase' => CatTipoImpresora::class, 'tabla' => 'tblCatTipoImpresora', 'pk' => 'id_TipoImpresora'],
        'tipo-camara' => ['nombre' => 'Tipos de Cámara', 'clase' => CatTipoCamara::class, 'tabla' => 'tblCatTipoCamara', 'pk' => 'id_TipoCamara'],
        'tipo-gps' => ['nombre' => 'Tipos de GPS', 'clase' => CatTipoGps::class, 'tabla' => 'tblCatTipoGps', 'pk' => 'id_TipoGps'],
    ];

    public function index(?string $categoria = null)
    {
        $categoria = $categoria ?: 'marca';
        $def = $this->definicion($categoria);

        $consulta = $def['clase']::orderBy('nombre');
        if ($categoria === 'modelo') {
            $consulta->with('marca:id_Marca,nombre');
        }

        return view('configuracion.catalogo_equipo', [
            'categoria' => $categoria,
            'categorias' => self::CATEGORIAS,
            'def' => $def,
            'registros' => $consulta->get(),
            'padres' => $categoria === 'modelo'
                ? CatMarca::where('activo', true)->orderBy('nombre')->get()
                : collect(),
        ]);
    }

    public function store(Request $request, string $categoria)
    {
        $def = $this->definicion($categoria);
        $data = $request->validate($this->reglas($categoria, $request));

        $def['clase']::create($data + ['fechaAlta' => now(), 'activo' => true]);

        return redirect()
            ->route('configuracion.catalogoEquipo.index', $categoria)
            ->with('success', $def['nombre'] . ': registro creado correctamente');
    }

    public function update(Request $request, string $categoria, $id)
    {
        $def = $this->definicion($categoria);
        $registro = $def['clase']::findOrFail($id);
        $data = $request->validate($this->reglas($categoria, $request, $id));

        $registro->update($data);

        return redirect()
            ->route('configuracion.catalogoEquipo.index', $categoria)
            ->with('success', $def['nombre'] . ': registro actualizado correctamente');
    }

    public function toggle(string $categoria, $id)
    {
        $def = $this->definicion($categoria);
        $registro = $def['clase']::findOrFail($id);
        $registro->activo = !$registro->activo;
        $registro->save();

        return back()->with('success', $registro->activo ? 'Registro activado' : 'Registro desactivado');
    }

    private function definicion(string $categoria): array
    {
        if (!isset(self::CATEGORIAS[$categoria])) {
            abort(404);
        }
        return self::CATEGORIAS[$categoria];
    }

    private function reglas(string $categoria, Request $request, $id = null): array
    {
        $def = $this->definicion($categoria);
        $reglas = [
            'nombre' => ['required', 'string', 'max:100'],
        ];

        if ($categoria === 'modelo') {
            $reglas['id_Marca'] = ['required', 'integer', Rule::exists('tblCatMarca', 'id_Marca')];
            $reglas['nombre'][] = Rule::unique('tblCatModelo', 'nombre')
                ->ignore($id, 'id_Modelo')
                ->where(fn ($q) => $q->where('id_Marca', $request->id_Marca));
        } else {
            $reglas['nombre'][] = Rule::unique($def['tabla'], 'nombre')->ignore($id, $def['pk']);
        }

        return $reglas;
    }
}
