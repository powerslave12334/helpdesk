<?php

namespace App\Http\Controllers\Inventario;

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
use App\Models\Departamento;
use App\Models\Equipo;
use App\Models\TipoEquipo;
use App\Models\Estatus;
use App\Models\Oficina;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EquipoController extends Controller
{
    /** Opciones fijas de "Puertos" para Monitor (además de "Otros puertos"). */
    private const PUERTOS_FIJOS = ['HDMI', 'VGA', 'DisplayPort', 'DVI', 'USB', 'Audio', 'RJ45'];

    /** Opciones fijas de "Funciones" para Impresora. */
    private const FUNCIONES_FIJAS = ['Imprimir', 'Copiar', 'Escanear', 'Fax'];

    /** Opciones fijas de "Resolución" para Cámara. */
    private const RESOLUCIONES_CAMARA = ['1MP', '2MP', '4MP', '5MP', '8MP'];

    /** Opciones fijas de "Conexión" para Cámara. */
    private const CONEXIONES_CAMARA = ['PoE', 'Cableado', 'Inalámbrica'];

    public function index()
    {
        $equipos = Equipo::with(['tipoEquipo', 'estatus', 'oficina', 'asignaciones.usuario'])
            ->get()
            ->map(function ($equipo) {
                $asignacionActiva = $equipo->asignaciones->where('activo', true)->whereNull('fechaBaja')->first();
                $equipo->asignado_a = $asignacionActiva ? $asignacionActiva->usuario->nombre . ' ' . $asignacionActiva->usuario->app : null;
                $equipo->estado_asignacion = $asignacionActiva ? 'asignado' : 'disponible';
                return $equipo;
            });
        $tiposEquipo = TipoEquipo::where('activo', true)->get();
        $estatus = Estatus::where('activo', true)->where('id_Modulos', 2)->get();
        $oficinas = Oficina::where('activo', true)->get();

        return view('inventario.equipo', compact('equipos', 'tiposEquipo', 'estatus', 'oficinas'));
    }

    public function seleccionarTipo()
    {
        $tiposEquipo = TipoEquipo::where('activo', true)->get();
        return view('inventario.equipo_seleccionar_tipo', compact('tiposEquipo'));
    }

    public function create($tipo = null)
    {
        $tiposEquipo = TipoEquipo::where('activo', true)->get();
        $estatus = Estatus::where('activo', true)->where('id_Modulos', 2)->get();
        $oficinas = Oficina::where('activo', true)->get();
        $tipoSeleccionado = $tipo ? TipoEquipo::find($tipo) : null;
        $catalogos = $this->catalogosEquipo([
            'marca' => old('marca'),
            'ubicacion' => old('ubicacion'),
            'procesador' => old('procesador'),
            'almacenamiento' => old('almacenamiento'),
            'sistemaOperativo' => old('sistemaOperativo'),
            'tipoTeclado' => old('tipoTeclado'),
            'conectividad' => old('conectividad'),
            'tipoImpresora' => old('tipoImpresora'),
            'tipoCamara' => old('tipoCamara'),
            'tipoGps' => old('tipoGps'),
        ]);
        $puertosFijos = self::PUERTOS_FIJOS;
        return view('inventario.equipo_create', compact('tiposEquipo', 'estatus', 'oficinas', 'tipoSeleccionado', 'catalogos', 'puertosFijos'));
    }

    public function store(Request $request)
    {
        $request->validate($this->reglasValidacion($request));

        if ($request->numeroSerie) {
            $existe = Equipo::where('activo', true)
                ->whereRaw('LOWER(numeroSerie) = ?', strtolower($request->numeroSerie))
                ->first();
            if ($existe) {
                return back()->with('error', 'El número de serie ya existe');
            }
        }

        DB::beginTransaction();
        try {
            $equipo = Equipo::create([
                'marca' => $request->marca,
                'modelo' => $request->modelo ?: '',
                'id_TipoEquipo' => $request->id_TipoEquipo,
                'id_Estatus' => $request->id_Estatus,
                'numeroSerie' => $request->numeroSerie,
                'nombreEquipo' => $request->nombreEquipo,
                'ubicacion' => $request->ubicacion,
                'observaciones' => $request->observaciones,
                'id_Oficinas' => $request->id_Oficinas,
                'id_PeriodoMantenimiento' => $request->id_PeriodoMantenimiento ?? 0,
                'fechaUltimoMantenimiento' => $request->fechaUltimoMantenimiento ?: null,
                'esCompartido' => $request->has('esCompartido') ? 1 : 0,
                'serieMotherboard' => $request->serieMotherboard,
                'procesador' => $request->procesador,
                'memoriaRam' => $request->memoriaRam,
                'almacenamiento' => $request->almacenamiento,
                'tamanoAlmacenamiento' => $request->tamanoAlmacenamiento,
                'sistemaOperativo' => $request->sistemaOperativo,
                'ip' => $request->ip,
                'mac' => $request->mac,
                'estadoGarantia' => $request->estadoGarantia,
                'fechaFinGarantia' => $request->fechaFinGarantia ?: null,
                'especificaciones' => $request->especificaciones,
                'tamano' => $request->tamano,
                'puertos' => $this->puertosTexto($request),
                'costo' => $request->costo === null || $request->costo === '' ? null : $request->costo,
                'fechaAdquisicion' => $request->fechaAdquisicion ?: null,
                'tipoTeclado' => $request->tipoTeclado,
                'conectividad' => $request->conectividad,
                'capacidad' => $request->capacidad,
                'voltajeEntrada' => $request->voltajeEntrada,
                'voltajeSalida' => $request->voltajeSalida,
                'contactos' => $request->contactos === null || $request->contactos === '' ? null : $request->contactos,
                'tipoImpresora' => $request->tipoImpresora,
                'colorImpresion' => $request->colorImpresion,
                'multifuncion' => $request->multifuncion === null || $request->multifuncion === '' ? null : (int) $request->multifuncion,
                'funciones' => $this->funcionesTexto($request),
                'tipoCamara' => $request->tipoCamara,
                'resolucion' => $request->resolucion,
                'conexion' => $request->conexion,
                'tipoGps' => $request->tipoGps,
                'imei' => $request->imei,
                'operador' => $request->operador,
                'lineaSim' => $request->lineaSim,
                'fechaAlta' => now(),
                'activo' => true,
            ]);

            DB::commit();
            return redirect()->route('inventario.equipo.index')->with('success', 'Equipo creado correctamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al crear equipo: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $equipo = Equipo::with(['tipoEquipo', 'estatus', 'oficina'])->findOrFail($id);
        $tiposEquipo = TipoEquipo::where('activo', true)->get();
        $estatus = Estatus::where('activo', true)->where('id_Modulos', 2)->get();
        $oficinas = Oficina::where('activo', true)->get();
        $catalogos = $this->catalogosEquipo([
            'marca' => old('marca', $equipo->marca),
            'ubicacion' => old('ubicacion', $equipo->ubicacion),
            'procesador' => old('procesador', $equipo->procesador),
            'almacenamiento' => old('almacenamiento', $equipo->almacenamiento),
            'sistemaOperativo' => old('sistemaOperativo', $equipo->sistemaOperativo),
            'tipoTeclado' => old('tipoTeclado', $equipo->tipoTeclado),
            'conectividad' => old('conectividad', $equipo->conectividad),
            'tipoImpresora' => old('tipoImpresora', $equipo->tipoImpresora),
            'tipoCamara' => old('tipoCamara', $equipo->tipoCamara),
            'tipoGps' => old('tipoGps', $equipo->tipoGps),
        ]);
        $puertosFijos = self::PUERTOS_FIJOS;

        return view('inventario.equipo_edit', compact('equipo', 'tiposEquipo', 'estatus', 'oficinas', 'catalogos', 'puertosFijos'));
    }

    public function update(Request $request, $id)
    {
        $request->validate($this->reglasValidacion($request));

        if ($request->numeroSerie) {
            $existe = Equipo::where('activo', true)
                ->whereRaw('LOWER(numeroSerie) = ?', strtolower($request->numeroSerie))
                ->where('id_Equipo', '!=', $id)
                ->first();
            if ($existe) {
                return back()->with('error', 'El número de serie ya existe');
            }
        }

        DB::beginTransaction();
        try {
            $equipo = Equipo::findOrFail($id);
            $equipo->update([
                'marca' => $request->marca,
                'modelo' => $request->modelo ?: '',
                'id_TipoEquipo' => $request->id_TipoEquipo,
                'id_Estatus' => $request->id_Estatus,
                'numeroSerie' => $request->numeroSerie,
                'nombreEquipo' => $request->nombreEquipo,
                'ubicacion' => $request->ubicacion,
                'observaciones' => $request->observaciones,
                'id_Oficinas' => $request->id_Oficinas,
                'id_PeriodoMantenimiento' => $request->id_PeriodoMantenimiento ?? 0,
                'fechaUltimoMantenimiento' => $request->fechaUltimoMantenimiento ?: null,
                'esCompartido' => $request->has('esCompartido') ? 1 : 0,
                'serieMotherboard' => $request->serieMotherboard,
                'procesador' => $request->procesador,
                'memoriaRam' => $request->memoriaRam,
                'almacenamiento' => $request->almacenamiento,
                'tamanoAlmacenamiento' => $request->tamanoAlmacenamiento,
                'sistemaOperativo' => $request->sistemaOperativo,
                'ip' => $request->ip,
                'mac' => $request->mac,
                'estadoGarantia' => $request->estadoGarantia,
                'fechaFinGarantia' => $request->fechaFinGarantia ?: null,
                'especificaciones' => $request->especificaciones,
                'tamano' => $request->tamano,
                'puertos' => $this->puertosTexto($request),
                'costo' => $request->costo === null || $request->costo === '' ? null : $request->costo,
                'fechaAdquisicion' => $request->fechaAdquisicion ?: null,
                'tipoTeclado' => $request->tipoTeclado,
                'conectividad' => $request->conectividad,
                'capacidad' => $request->capacidad,
                'voltajeEntrada' => $request->voltajeEntrada,
                'voltajeSalida' => $request->voltajeSalida,
                'contactos' => $request->contactos === null || $request->contactos === '' ? null : $request->contactos,
                'tipoImpresora' => $request->tipoImpresora,
                'colorImpresion' => $request->colorImpresion,
                'multifuncion' => $request->multifuncion === null || $request->multifuncion === '' ? null : (int) $request->multifuncion,
                'funciones' => $this->funcionesTexto($request),
                'tipoCamara' => $request->tipoCamara,
                'resolucion' => $request->resolucion,
                'conexion' => $request->conexion,
                'tipoGps' => $request->tipoGps,
                'imei' => $request->imei,
                'operador' => $request->operador,
                'lineaSim' => $request->lineaSim,
            ]);

            DB::commit();
            return redirect()->route('inventario.equipo.index')->with('success', 'Equipo actualizado correctamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al actualizar: ' . $e->getMessage());
        }
    }

    public function toggleActivo($id)
    {
        $equipo = Equipo::findOrFail($id);
        $equipo->activo = !$equipo->activo;
        $equipo->save();
        return back()->with('success', $equipo->activo ? 'Equipo activado' : 'Equipo desactivado');
    }

    /**
     * Opciones de los dropdowns del formulario PC Escritorio (CPU).
     * Los valores actuales que ya no estén en el catálogo se agregan como
     * opción para no perder datos al editar equipos existentes.
     */
    private function catalogosEquipo(array $actuales = []): array
    {
        return [
            'marcas' => $this->conValorActual(
                CatMarca::where('activo', true)->orderBy('nombre')->pluck('nombre'),
                $actuales['marca'] ?? null
            ),
            'modelos' => CatModelo::where('activo', true)
                ->with('marca:id_Marca,nombre')
                ->orderBy('nombre')
                ->get(),
            'departamentos' => $this->conValorActual(
                Departamento::where('activo', true)->orderBy('nombre')->pluck('nombre'),
                $actuales['ubicacion'] ?? null
            ),
            'procesadores' => $this->conValorActual(
                CatProcesador::where('activo', true)->orderBy('nombre')->pluck('nombre'),
                $actuales['procesador'] ?? null
            ),
            'tiposAlmacenamiento' => $this->conValorActual(
                CatTipoAlmacenamiento::where('activo', true)->orderBy('nombre')->pluck('nombre'),
                $actuales['almacenamiento'] ?? null
            ),
            'sistemasOperativos' => $this->conValorActual(
                CatSistemaOperativo::where('activo', true)->orderBy('nombre')->pluck('nombre'),
                $actuales['sistemaOperativo'] ?? null
            ),
            'tiposTeclado' => $this->conValorActual(
                CatTipoTeclado::where('activo', true)->orderBy('nombre')->pluck('nombre'),
                $actuales['tipoTeclado'] ?? null
            ),
            'conectividades' => $this->conValorActual(
                CatConectividad::where('activo', true)->orderBy('nombre')->pluck('nombre'),
                $actuales['conectividad'] ?? null
            ),
            'tiposImpresora' => $this->conValorActual(
                CatTipoImpresora::where('activo', true)->orderBy('nombre')->pluck('nombre'),
                $actuales['tipoImpresora'] ?? null
            ),
            'tiposCamara' => $this->conValorActual(
                CatTipoCamara::where('activo', true)->orderBy('nombre')->pluck('nombre'),
                $actuales['tipoCamara'] ?? null
            ),
            'tiposGps' => $this->conValorActual(
                CatTipoGps::where('activo', true)->orderBy('nombre')->pluck('nombre'),
                $actuales['tipoGps'] ?? null
            ),
        ];
    }

    private function conValorActual($valores, ?string $actual)
    {
        if ($actual !== null && $actual !== ''
            && !$valores->contains(fn ($v) => strcasecmp((string) $v, $actual) === 0)) {
            $valores->push($actual);
        }
        return $valores;
    }

    private function reglasValidacion(Request $request): array
    {
        $reglas = [
            'marca' => 'required|string|max:100',
            'modelo' => ['string', 'max:100'],
            'id_TipoEquipo' => 'required|integer',
            'id_Estatus' => 'required|integer',
            'numeroSerie' => 'nullable|string|max:100',
            'nombreEquipo' => 'nullable|string|max:200',
            'ubicacion' => 'nullable|string|max:200',
            'observaciones' => 'nullable|string',
            'id_Oficinas' => 'nullable|integer',
            'esCompartido' => 'sometimes|boolean',
            'serieMotherboard' => 'nullable|string|max:150',
            'procesador' => 'nullable|string|max:150',
            'memoriaRam' => 'nullable|string|max:100',
            'almacenamiento' => 'nullable|string|max:150',
            'tamanoAlmacenamiento' => 'nullable|string|max:50',
            'sistemaOperativo' => 'nullable|string|max:100',
            'ip' => 'nullable|string|max:45',
            'mac' => 'nullable|string|max:50',
            'estadoGarantia' => 'nullable|string|max:50',
            'fechaFinGarantia' => 'nullable|date',
            'especificaciones' => 'nullable|string',
            'fechaAdquisicion' => 'nullable|date',
            'costo' => 'nullable|numeric|min:0',
            'tamano' => 'nullable|string|max:50',
            'puertos' => 'nullable|array',
            'puertos.*' => 'string|max:50',
            'puertosOtros' => 'nullable|string|max:150',
            'tipoTeclado' => 'nullable|string|max:100',
            'conectividad' => 'nullable|string|max:100',
            'capacidad' => 'nullable|string|max:50',
            'voltajeEntrada' => 'nullable|string|max:20',
            'voltajeSalida' => 'nullable|string|max:20',
            'contactos' => 'nullable|integer|min:0|max:999',
            'tipoImpresora' => 'nullable|string|max:50',
            'colorImpresion' => 'nullable|string|in:Color,Monocromática',
            'multifuncion' => 'nullable|in:0,1',
            'funciones' => 'nullable|array',
            'funciones.*' => ['string', 'in:' . implode(',', self::FUNCIONES_FIJAS)],
            'tipoCamara' => 'nullable|string|max:50',
            'resolucion' => ['nullable', 'in:' . implode(',', self::RESOLUCIONES_CAMARA)],
            'conexion' => ['nullable', 'in:' . implode(',', self::CONEXIONES_CAMARA)],
            'tipoGps' => 'nullable|string|max:50',
            'imei' => 'nullable|string|max:20|regex:/^[0-9]{15}$/',
            'operador' => 'nullable|string|in:' . implode(',', config('equipo_perfiles.operadores_gps', [])),
            'lineaSim' => 'nullable|string|max:20',
        ];

        // El campo Modelo no existe para Teclado y Mouse (se guarda vacío).
        $sinModelo = config('equipo_perfiles.sin_modelo', []);
        if (!in_array((int) $request->id_TipoEquipo, $sinModelo, true)) {
            $reglas['modelo'][] = 'required';
        }

        // El Tipo de GPS es obligatorio cuando el equipo es un GPS.
        if ((int) $request->id_TipoEquipo === (int) config('equipo_perfiles.ids.gps')) {
            $reglas['tipoGps'] = ['required', 'string', 'max:50'];
        }

        return $reglas;
    }

    /** Une los checkboxes de puertos con "Otros puertos" en un solo texto. */
    private function puertosTexto(Request $request): ?string
    {
        $partes = is_array($request->puertos) ? $request->puertos : [];
        if ($request->puertosOtros) {
            $partes[] = $request->puertosOtros;
        }
        $partes = array_values(array_filter(array_map('trim', $partes)));
        return $partes ? implode(', ', $partes) : null;
    }

    /** Une los checkboxes de funciones de Impresora en un solo texto. */
    private function funcionesTexto(Request $request): ?string
    {
        $partes = is_array($request->funciones) ? $request->funciones : [];
        $partes = array_values(array_filter(array_map('trim', $partes)));
        return $partes ? implode(', ', $partes) : null;
    }
}
