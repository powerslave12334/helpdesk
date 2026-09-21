<?php

namespace App\Http\Controllers;

use App\Services\CorreoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LoginController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'usuario' => 'required|string',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials, false)) {
            $request->session()->regenerate();

            $user = Auth::user();
            session([
                'Usuario' => $user->login->usuario ?? '',
                'NombreUsuario' => $user->nombreCompleto(),
                'IdUsuario' => $user->getKey(),
            ]);

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'usuario' => 'Las credenciales proporcionadas no son correctas.',
        ])->onlyInput('usuario');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function recuperarPassword(Request $request)
    {
        $request->validate([
            'usuarioRecuperacion' => 'required|string',
        ]);

        $usuario = $request->input('usuarioRecuperacion');

        $login = DB::table('tblLogin as l')
            ->join('tblUsuario as u', 'u.id_Usuario', '=', 'l.id_Usuario')
            ->where('l.usuario', $usuario)
            ->orWhere('u.correo', $usuario)
            ->select('l.id_Login', 'l.usuario', 'l.pass', 'u.correo', 'u.nombre', 'u.app', 'u.apm')
            ->first();

        if (!$login) {
            return back()->withErrors([
                'usuarioRecuperacion' => 'No se encontró una cuenta asociada al usuario o correo ingresado.',
            ])->onlyInput('usuarioRecuperacion');
        }

        if (empty($login->correo)) {
            return back()->withErrors([
                'usuarioRecuperacion' => 'La cuenta encontrada no tiene un correo electrónico registrado.',
            ])->onlyInput('usuarioRecuperacion');
        }

        $tempPassword = 'Hd-' . substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 10);
        $hashedPassword = hash('sha512', $tempPassword);
        $oldPassword = $login->pass;

        DB::table('tblLogin')
            ->where('id_Login', $login->id_Login)
            ->update(['pass' => $hashedPassword]);

        $correoService = new CorreoService();
        $asunto = 'Help Desk - Recuperación de Contraseña';
        $nombreUsuario = trim(($login->nombre ?? '') . ' ' . ($login->app ?? '') . ' ' . ($login->apm ?? ''));
        $htmlCuerpo = "
            <div style='font-family: Arial, sans-serif; padding: 20px;'>
                <h2 style='color: #333;'>Recuperación de Contraseña</h2>
                <p>Hola <strong>{$nombreUsuario}</strong>,</p>
                <p>Se ha generado una contraseña temporal para tu cuenta <strong>{$login->usuario}</strong>.</p>
                <p>Tu nueva contraseña temporal es:</p>
                <div style='background-color: #f4f4f4; padding: 15px; border-radius: 5px; text-align: center; margin: 20px 0;'>
                    <span style='font-size: 18px; font-weight: bold; color: #d9534f; letter-spacing: 2px;'>{$tempPassword}</span>
                </div>
                <p>Por favor, inicia sesión con esta contraseña y cámbiala lo antes posible.</p>
                <p style='color: #999; font-size: 12px;'>Si no solicitaste este cambio, contacte al administrador del sistema.</p>
            </div>
        ";

        $resultado = $correoService->enviarCorreo($login->correo, $asunto, $htmlCuerpo);

        if (!$resultado['ok']) {
            DB::table('tblLogin')
                ->where('id_Login', $login->id_Login)
                ->update(['pass' => $oldPassword]);

            return back()->withErrors([
                'usuarioRecuperacion' => 'No se pudo enviar el correo de recuperación. Intente más tarde.',
            ])->onlyInput('usuarioRecuperacion');
        }

        return back()->with('success', 'Se ha enviado una contraseña temporal al correo registrado.');
    }
}
