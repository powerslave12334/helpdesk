<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
}
