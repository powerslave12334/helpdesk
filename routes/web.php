<?php

use App\Http\Controllers\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\Configuracion\RolController;
use App\Http\Controllers\Configuracion\EstatusController;
use App\Http\Controllers\Configuracion\ModuloController;
use App\Http\Controllers\Configuracion\AreaController;
use App\Http\Controllers\Configuracion\DeptoController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Tickets
    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::post('/tickets/desarrollo', [TicketController::class, 'storeDesarrollo'])->name('tickets.storeDesarrollo');
    Route::get('/tickets/{id}', [TicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/comentario', [TicketController::class, 'addComment'])->name('tickets.addComment');
    Route::post('/tickets/evidencia', [TicketController::class, 'addEvidence'])->name('tickets.addEvidence');
    Route::post('/tickets/gestion', [TicketController::class, 'updateManagement'])->name('tickets.updateManagement');
    Route::post('/tickets/cerrar/{id}', [TicketController::class, 'closeWithApproval'])->name('tickets.close');
    Route::get('/tickets/modulos/{id}', [TicketController::class, 'obtenerModulos'])->name('tickets.modulos');
    Route::get('/tickets/submodulos/{id}', [TicketController::class, 'obtenerSubmodulos'])->name('tickets.submodulos');

    // Empleados
    Route::get('/empleados', [EmpleadoController::class, 'index'])->name('empleados.index');
    Route::post('/empleados', [EmpleadoController::class, 'store'])->name('empleados.store');
    Route::put('/empleados/{id}', [EmpleadoController::class, 'update'])->name('empleados.update');
    Route::post('/empleados/acceso', [EmpleadoController::class, 'guardarAcceso'])->name('empleados.acceso');
    Route::post('/empleados/restaurar-password/{id}', [EmpleadoController::class, 'restaurarPassword'])->name('empleados.restaurarPassword');
    Route::post('/empleados/toggle/{id}', [EmpleadoController::class, 'toggleActivo'])->name('empleados.toggle');

    // Inventario
    Route::get('/inventario', [InventarioController::class, 'index'])->name('inventario.index');
    Route::post('/inventario', [InventarioController::class, 'store'])->name('inventario.store');
    Route::put('/inventario/{id}', [InventarioController::class, 'update'])->name('inventario.update');
    Route::post('/inventario/toggle/{id}', [InventarioController::class, 'toggleActivo'])->name('inventario.toggle');
    Route::get('/inventario/campos/{idTipo}', [InventarioController::class, 'obtenerCampos'])->name('inventario.campos');

    // Configuración
    Route::prefix('configuracion')->name('configuracion.')->group(function () {
        Route::get('/roles', [RolController::class, 'index'])->name('roles.index');
        Route::post('/roles', [RolController::class, 'store'])->name('roles.store');
        Route::put('/roles/{id}', [RolController::class, 'update'])->name('roles.update');
        Route::post('/roles/toggle/{id}', [RolController::class, 'toggleActivo'])->name('roles.toggle');

        Route::get('/estatus', [EstatusController::class, 'index'])->name('estatus.index');
        Route::post('/estatus', [EstatusController::class, 'store'])->name('estatus.store');
        Route::put('/estatus/{id}', [EstatusController::class, 'update'])->name('estatus.update');
        Route::post('/estatus/toggle/{id}', [EstatusController::class, 'toggleActivo'])->name('estatus.toggle');

        Route::get('/modulos', [ModuloController::class, 'index'])->name('modulos.index');
        Route::post('/modulos', [ModuloController::class, 'store'])->name('modulos.store');
        Route::put('/modulos/{id}', [ModuloController::class, 'update'])->name('modulos.update');

        Route::get('/areas', [AreaController::class, 'index'])->name('areas.index');
        Route::post('/areas', [AreaController::class, 'store'])->name('areas.store');
        Route::put('/areas/{id}', [AreaController::class, 'update'])->name('areas.update');
        Route::post('/areas/toggle/{id}', [AreaController::class, 'toggleActivo'])->name('areas.toggle');

        Route::get('/deptos', [DeptoController::class, 'index'])->name('deptos.index');
        Route::post('/deptos', [DeptoController::class, 'store'])->name('deptos.store');
        Route::put('/deptos/{id}', [DeptoController::class, 'update'])->name('deptos.update');
        Route::post('/deptos/toggle/{id}', [DeptoController::class, 'toggleActivo'])->name('deptos.toggle');
    });
});
