<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Models\Login;
use App\Models\Departamento;
use App\Models\Area;
use App\Models\Oficina;
use App\Models\TipoEmpleado;
use App\Models\Rol;
use App\Models\RolesUsuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EmpleadoController extends Controller
{
    public function index()
    {
        $idUsuario = session('IdUsuario');
        $esAdmin = $this->esAdmin($idUsuario);

        $usuarios = Usuario::with(['departamento', 'area', 'oficina', 'tipoEmpleado', 'login', 'roles'])
            ->where('activo', true)
            ->get();

        $deptos = Departamento::where('activo', true)->get();
        $areas = Area::where('activo', true)->get();
        $oficinas = Oficina::where('activo', true)->get();
        $tiposEmpleado = TipoEmpleado::where('activo', true)->get();
        $roles = Rol::where('activo', true)->get();

        return view('empleados.index', compact('usuarios', 'deptos', 'areas', 'oficinas', 'tiposEmpleado', 'roles', 'esAdmin'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'app' => 'required|string|max:100',
            'apm' => 'nullable|string|max:100',
            'correo' => 'required|email|max:200',
            'telefono' => 'nullable|string|max:20',
            'id_Depto' => 'required|integer',
            'id_Area' => 'required|integer',
            'id_Oficinas' => 'nullable|integer',
            'id_TipoEmpleado' => 'required|integer',
            'esTecnico' => 'sometimes|boolean',
        ]);

        $usuario = new Usuario();
        $usuario->nombre = $request->nombre;
        $usuario->app = $request->app;
        $usuario->apm = $request->apm;
        $usuario->correo = $request->correo;
        $usuario->telefono = $request->telefono;
        $usuario->id_Depto = $request->id_Depto;
        $usuario->id_Area = $request->id_Area;
        $usuario->id_Oficinas = $request->id_Oficinas;
        $usuario->id_TipoEmpleado = $request->id_TipoEmpleado;
        $usuario->esTecnico = $request->has('esTecnico') ? 1 : 0;
        $usuario->fechaAlta = now();
        $usuario->activo = true;
        $usuario->save();

        return redirect()->route('empleados.index')->with('success', 'Empleado creado correctamente');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'app' => 'required|string|max:100',
            'apm' => 'nullable|string|max:100',
            'correo' => 'required|email|max:200',
            'telefono' => 'nullable|string|max:20',
            'id_Depto' => 'required|integer',
            'id_Area' => 'required|integer',
            'id_Oficinas' => 'nullable|integer',
            'id_TipoEmpleado' => 'required|integer',
            'esTecnico' => 'sometimes|boolean',
        ]);

        $usuario = Usuario::findOrFail($id);
        $usuario->update([
            'nombre' => $request->nombre,
            'app' => $request->app,
            'apm' => $request->apm,
            'correo' => $request->correo,
            'telefono' => $request->telefono,
            'id_Depto' => $request->id_Depto,
            'id_Area' => $request->id_Area,
            'id_Oficinas' => $request->id_Oficinas,
            'id_TipoEmpleado' => $request->id_TipoEmpleado,
            'esTecnico' => $request->has('esTecnico') ? 1 : 0,
        ]);

        return redirect()->route('empleados.index')->with('success', 'Empleado actualizado correctamente');
    }

    public function guardarAcceso(Request $request)
    {
        $request->validate([
            'id_Usuario' => 'required|integer',
            'usuario' => 'required|string|max:100',
            'pass' => 'required|string|min:6',
        ]);

        $login = Login::where('id_Usuario', $request->id_Usuario)->first();

        if ($login) {
            $login->usuario = $request->usuario;
            $login->pass = strtoupper(hash('sha512', $request->pass));
            $login->save();
        } else {
            $existe = Login::where('usuario', $request->usuario)->where('activo', true)->first();
            if ($existe) {
                return back()->with('error', 'El nombre de usuario ya existe');
            }

            Login::create([
                'id_Usuario' => $request->id_Usuario,
                'usuario' => $request->usuario,
                'pass' => strtoupper(hash('sha512', $request->pass)),
                'fechaAlta' => now(),
                'activo' => true,
            ]);
        }

        return redirect()->route('empleados.index')->with('success', 'Acceso guardado correctamente');
    }

    public function restaurarPassword($id)
    {
        $idUsuario = session('IdUsuario');
        $login = Login::where('id_Usuario', $id)->where('activo', true)->first();

        if (!$login) {
            return back()->with('error', 'No se encontró acceso para este usuario');
        }

        $tempPassword = 'Hd-' . Str::random(10);
        $login->pass = strtoupper(hash('sha512', $tempPassword));
        $login->save();

        return back()->with('success', 'Contraseña restaurada. Nueva contraseña: ' . $tempPassword);
    }

    public function toggleActivo($id)
    {
        $usuario = Usuario::findOrFail($id);
        $usuario->activo = !$usuario->activo;
        $usuario->save();

        $login = Login::where('id_Usuario', $id)->first();
        if ($login) {
            $login->activo = $usuario->activo;
            $login->save();
        }

        return back()->with('success', $usuario->activo ? 'Usuario activado' : 'Usuario desactivado');
    }

    private function esAdmin($idUsuario)
    {
        return DB::table('tblRolesUsuarios')
            ->join('tblRoles', 'tblRoles.id_Rol', '=', 'tblRolesUsuarios.id_Rol')
            ->where('tblRolesUsuarios.id_Usuario', $idUsuario)
            ->where('tblRolesUsuarios.activo', true)
            ->where(function ($q) {
                $q->where('tblRoles.nombre', 'like', '%admin%')
                  ->orWhere('tblRoles.nombre', 'root');
            })
            ->exists();
    }
}
