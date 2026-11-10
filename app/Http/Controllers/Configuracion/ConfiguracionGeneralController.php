<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\ConfiguracionSistema;
use App\Services\CorreoService;
use App\Support\ConexionActiva;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConfiguracionGeneralController extends Controller
{
    public function index()
    {
        $configuraciones = ConfiguracionSistema::all();
        $conexionActiva = ConexionActiva::actual();
        $conexiones = ConexionActiva::OPCIONES;
        $infoConexiones = collect($conexiones)->mapWithKeys(
            fn ($datos, $clave) => [$clave => ConexionActiva::info($clave)]
        );

        $correoService = app(CorreoService::class);
        $correosAutomaticos = $correoService->automaticosActivos();
        $smtp = $this->estadoSmtp();

        return view('configuracion.configuracion_general', compact(
            'configuraciones',
            'conexionActiva',
            'infoConexiones',
            'correosAutomaticos',
            'smtp'
        ));
    }

    /**
     * Enciende o apaga el envío de correos automáticos
     * (llave `CorreoAutomaticosActivo`, leída por CorreoService).
     */
    public function toggleCorreosAutomaticos()
    {
        $actual = app(CorreoService::class)->automaticosActivos();
        $nuevo = ! $actual;

        ConfiguracionSistema::updateOrCreate(
            ['clave' => 'CorreoAutomaticosActivo'],
            [
                'valor' => $nuevo ? '1' : '0',
                'descripcion' => 'Activa el envío automático de correos (notificaciones de tickets y mantenimientos).',
                'fechaAlta' => now(),
                'activo' => true,
            ]
        );

        return back()->with(
            'success',
            $nuevo ? 'Correos automáticos activados' : 'Correos automáticos desactivados'
        );
    }

    /**
     * Resumen del SMTP configurado para mostrar en el panel.
     */
    private function estadoSmtp(): array
    {
        $claves = ['CorreoServidorSmtp', 'CorreoPuertoSmtp', 'CorreoRemitente', 'CorreoUsuario', 'CorreoPassword'];

        $valores = DB::table('tblConfiguracionSistema')
            ->where('activo', true)
            ->whereIn('clave', $claves)
            ->pluck('valor', 'clave');

        $servidor = $valores['CorreoServidorSmtp'] ?? '';
        $puerto = $valores['CorreoPuertoSmtp'] ?? '';
        $remitente = $valores['CorreoRemitente'] ?? '';
        $usuario = $valores['CorreoUsuario'] ?? '';
        $password = $valores['CorreoPassword'] ?? '';

        $incompletos = array_values(array_filter([
            empty($servidor) ? 'servidor' : null,
            empty($remitente) ? 'remitente' : null,
            empty($usuario) ? 'usuario' : null,
            empty($password) ? 'contraseña' : null,
        ]));

        return [
            'servidor' => $servidor,
            'puerto' => $puerto,
            'remitente' => $remitente,
            'completo' => empty($incompletos),
            'faltantes' => $incompletos,
        ];
    }

    public function store(Request $request)
    {
        $request->validate([
            'clave' => 'required|string|max:100',
            'valor' => 'required|string',
            'descripcion' => 'nullable|string|max:250',
        ]);

        $existe = ConfiguracionSistema::whereRaw('LOWER(clave) = ?', strtolower($request->clave))->first();
        if ($existe) {
            return back()->with('error', 'Esta clave ya existe');
        }

        ConfiguracionSistema::create([
            'clave' => $request->clave,
            'valor' => $request->valor,
            'descripcion' => $request->descripcion,
            'fechaAlta' => now(),
            'activo' => true,
        ]);

        return redirect()->route('configuracion.configGeneral.index')->with('success', 'Configuración creada');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'clave' => 'required|string|max:100',
            'valor' => 'required|string',
            'descripcion' => 'nullable|string|max:250',
        ]);

        $existe = ConfiguracionSistema::whereRaw('LOWER(clave) = ?', strtolower($request->clave))
            ->where('id_ConfiguracionSistema', '!=', $id)->first();
        if ($existe) {
            return back()->with('error', 'Esta clave ya existe');
        }

        ConfiguracionSistema::findOrFail($id)->update([
            'clave' => $request->clave,
            'valor' => $request->valor,
            'descripcion' => $request->descripcion,
        ]);

        return redirect()->route('configuracion.configGeneral.index')->with('success', 'Configuración actualizada');
    }

    public function toggleActivo($id)
    {
        $config = ConfiguracionSistema::findOrFail($id);
        $config->activo = ! $config->activo;
        $config->save();

        return back()->with('success', $config->activo ? 'Activado' : 'Desactivado');
    }
}
