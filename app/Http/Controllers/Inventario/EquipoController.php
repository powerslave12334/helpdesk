<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use App\Models\CatConectividad;
use App\Models\CatMarca;
use App\Models\CatModelo;
use App\Models\CatProcesador;
use App\Models\CatSistemaOperativo;
use App\Models\CatTipoAlmacenamiento;
use App\Models\CatTipoCamara;
use App\Models\CatTipoGps;
use App\Models\CatTipoImpresora;
use App\Models\CatTipoTeclado;
use App\Models\Departamento;
use App\Models\Equipo;
use App\Models\Estatus;
use App\Models\InventoryField;
use App\Models\Oficina;
use App\Models\TipoEquipo;
use App\Models\TipoEquipoCampoConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
                $equipo->asignado_a = $asignacionActiva ? $asignacionActiva->usuario->nombre.' '.$asignacionActiva->usuario->app : null;
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
        $funcionesFijas = self::FUNCIONES_FIJAS;
        $resoluciones = self::RESOLUCIONES_CAMARA;
        $conexiones = self::CONEXIONES_CAMARA;
        $camposPorTipo = $this->obtenerMapaCamposPorTipo();
        $tipoSeleccionadoConfig = $tipoSeleccionado
            ? TipoEquipoCampoConfig::where('id_TipoEquipo', $tipoSeleccionado->id_TipoEquipo)->where('activo', true)->pluck('campo')->all()
            : [];

        // Campos personalizados (source=custom) del catálogo: sus inputs se
        // renderizan con data-campo y el JS los muestra según el tipo elegido.
        $camposCustom = InventoryField::custom()->activos()->orderBy('order')->get();

        return view('inventario.equipo_create', compact(
            'tiposEquipo', 'estatus', 'oficinas', 'tipoSeleccionado',
            'catalogos', 'puertosFijos', 'funcionesFijas', 'resoluciones', 'conexiones',
            'camposPorTipo', 'tipoSeleccionadoConfig', 'camposCustom'
        ));
    }

    public function store(Request $request)
    {
        $request->validate($this->reglasValidacion($request), [], $this->nombresAtributos());

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
                'attributes' => $this->atributosPersonalizados($request),
                'fechaAlta' => now(),
                'activo' => true,
            ]);

            DB::commit();

            return redirect()->route('inventario.equipo.index')->with('success', 'Equipo creado correctamente');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Error al crear equipo: '.$e->getMessage());
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
        $camposPorTipo = $this->obtenerMapaCamposPorTipo();
        $camposCustom = InventoryField::custom()->activos()->orderBy('order')->get();

        return view('inventario.equipo_edit', compact('equipo', 'tiposEquipo', 'estatus', 'oficinas', 'catalogos', 'puertosFijos', 'camposPorTipo', 'camposCustom'));
    }

    public function update(Request $request, $id)
    {
        $request->validate($this->reglasValidacion($request), [], $this->nombresAtributos());

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
                'attributes' => $this->atributosPersonalizados($request),
            ]);

            DB::commit();

            return redirect()->route('inventario.equipo.index')->with('success', 'Equipo actualizado correctamente');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Error al actualizar: '.$e->getMessage());
        }
    }

    public function toggleActivo($id)
    {
        $equipo = Equipo::findOrFail($id);
        $equipo->activo = ! $equipo->activo;
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
            && ! $valores->contains(fn ($v) => strcasecmp((string) $v, $actual) === 0)) {
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
            'funciones.*' => ['string', 'in:'.implode(',', self::FUNCIONES_FIJAS)],
            'tipoCamara' => 'nullable|string|max:50',
            'resolucion' => ['nullable', 'in:'.implode(',', self::RESOLUCIONES_CAMARA)],
            'conexion' => ['nullable', 'in:'.implode(',', self::CONEXIONES_CAMARA)],
            'tipoGps' => 'nullable|string|max:50',
            'imei' => 'nullable|string|max:20|regex:/^[0-9]{15}$/',
            'operador' => 'nullable|string|in:'.implode(',', config('equipo_perfiles.operadores_gps', [])),
            'lineaSim' => 'nullable|string|max:20',
        ];

        // Validar requeridos según la configuración en BD si existe para este tipo
        $configsBd = TipoEquipoCampoConfig::where('id_TipoEquipo', $request->id_TipoEquipo)
            ->where('activo', true)
            ->get()
            ->keyBy('campo');

        if ($configsBd->isNotEmpty()) {
            foreach ($configsBd as $campo => $cfg) {
                if ($cfg->es_requerido && isset($reglas[$campo])) {
                    if (is_array($reglas[$campo])) {
                        if (! in_array('required', $reglas[$campo], true)) {
                            // quitar 'nullable' si estaba
                            $reglas[$campo] = array_values(array_filter($reglas[$campo], fn ($r) => $r !== 'nullable'));
                            $reglas[$campo][] = 'required';
                        }
                    } elseif (is_string($reglas[$campo])) {
                        $partes = explode('|', $reglas[$campo]);
                        $partes = array_filter($partes, fn ($p) => $p !== 'nullable');
                        if (! in_array('required', $partes, true)) {
                            $partes[] = 'required';
                        }
                        $reglas[$campo] = implode('|', $partes);
                    }
                }
            }

            // Si el campo modelo NO está configurado como activo para este tipo (ej. Teclado, Mouse), no es required
            if (! $configsBd->has('modelo')) {
                $reglas['modelo'] = array_values(array_filter($reglas['modelo'], fn ($r) => $r !== 'required'));
            }

            // Reglas dinámicas para los campos personalizados asignados a este tipo
            $reglas += $this->reglasCamposPersonalizados($configsBd);
        } else {
            // Fallback a config estático
            $sinModelo = config('equipo_perfiles.sin_modelo', []);
            if (! in_array((int) $request->id_TipoEquipo, $sinModelo, true)) {
                $reglas['modelo'][] = 'required';
            }

            if ((int) $request->id_TipoEquipo === (int) config('equipo_perfiles.ids.gps')) {
                $reglas['tipoGps'] = ['required', 'string', 'max:50'];
            }
        }

        return $reglas;
    }

    /**
     * Construye las reglas de validación para los campos PERSONALIZADOS
     * (source = custom) asignados al tipo de equipo. Sus valores viven en
     * el JSON tblEquipo.attributes, por lo que se validan con notación
     * de puntos: attributes.nombreCampo.
     *
     * @param  Collection  $configsBd  asignaciones activas del tipo
     * @return array<string, array>
     */
    private function reglasCamposPersonalizados($configsBd): array
    {
        $nombresCustom = $configsBd->keys()
            ->intersect(
                InventoryField::custom()->activos()->pluck('name')
            )
            ->all();

        if (empty($nombresCustom)) {
            return [];
        }

        $campos = InventoryField::custom()->activos()->get()->keyBy('name');
        $reglas = [];

        foreach ($nombresCustom as $nombre) {
            $campo = $campos->get($nombre);
            if (! $campo) {
                continue;
            }

            $esRequerido = (bool) ($configsBd->get($nombre)->es_requerido ?? false);
            $reglas['attributes.'.$nombre] = $this->reglaParaTipo(
                $campo->type,
                $campo->validation_rules ?? [],
                $esRequerido
            );
        }

        return $reglas;
    }

    /**
     * Traduce el tipo de un campo del catálogo a reglas de validación de Laravel.
     */
    private function reglaParaTipo(string $tipo, array $opciones, bool $requerido): array
    {
        $base = match ($tipo) {
            'number', 'integer' => ['integer'],
            'decimal' => ['numeric'],
            'date' => ['date'],
            'datetime' => ['date'],
            'boolean' => ['boolean'],
            'select' => ! empty($opciones['opciones'])
                ? ['in:'.implode(',', $opciones['opciones'])]
                : ['string', 'max:255'],
            'tags' => ['array'],
            'textarea' => ['string'],
            default => ['string', 'max:255'],
        };

        if ($tipo === 'tags') {
            $base[] = 'max:20';
        }

        if ($requerido) {
            array_unshift($base, 'required');
        } else {
            array_unshift($base, 'nullable');
        }

        return $base;
    }

    /**
     * Nombres legibles para los mensajes de validación de los campos
     * personalizados: attributes.miCampo => "Capacidad de Batería".
     *
     * @return array<string, string>
     */
    private function nombresAtributos(): array
    {
        return InventoryField::custom()
            ->activos()
            ->get()
            ->mapWithKeys(fn ($f) => ['attributes.'.$f->name => $f->label])
            ->all();
    }

    /**
     * Separa los valores de los campos personalizados del resto de la petición
     * para guardarlos en el JSON attributes de tblEquipo.
     *
     * @return array<string, mixed>
     */
    private function atributosPersonalizados(Request $request): array
    {
        $nombres = InventoryField::custom()->activos()->pluck('name');
        $atributos = [];

        foreach ($nombres as $nombre) {
            $valor = $request->input('attributes.'.$nombre);

            // Los arrays (tags) llegan como array; el resto como escalar
            if (is_array($valor)) {
                $valor = array_values(array_filter($valor, fn ($v) => $v !== null && $v !== ''));
                if (empty($valor)) {
                    continue;
                }
            } elseif ($valor === null || $valor === '') {
                continue;
            }

            $atributos[$nombre] = $valor;
        }

        return $atributos;
    }

    /**
     * Devuelve la relación de campos activos por cada id_TipoEquipo desde la base de datos
     * agrupada: [ id_TipoEquipo => [ 'campo1', 'campo2' ] ]
     */
    private function obtenerMapaCamposPorTipo(): array
    {
        return TipoEquipoCampoConfig::where('activo', true)
            ->get()
            ->groupBy('id_TipoEquipo')
            ->map(fn ($items) => $items->pluck('campo')->all())
            ->all();
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
