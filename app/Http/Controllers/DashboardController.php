<?php

namespace App\Http\Controllers;

use App\Services\PermisosService;

class DashboardController extends Controller
{
    public function __construct(private PermisosService $permisosService)
    {
    }

    public function index()
    {
        $usuario = auth()->user();
        return view('dashboard', compact('usuario'));
    }
}
