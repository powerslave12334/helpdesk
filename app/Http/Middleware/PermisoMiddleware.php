<?php

namespace App\Http\Middleware;

use App\Services\PermisosService;
use Closure;
use Illuminate\Http\Request;

class PermisoMiddleware
{
    public function __construct(private PermisosService $permisosService)
    {
    }

    public function handle(Request $request, Closure $next, string $permiso)
    {
        $idUsuario = session('IdUsuario');

        if (!$idUsuario) {
            return redirect()->route('login');
        }

        $permitido = $this->permisosService->submoduloActivo($idUsuario, $permiso);

        if (!$permitido) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'No tienes permiso para realizar esta acción'], 403);
            }
            return redirect()->route('dashboard')->with('error', 'No tienes permiso para acceder a esta sección.');
        }

        return $next($request);
    }
}
