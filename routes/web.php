<?php

use App\Http\Controllers\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\Inventario\EquipoController;
use App\Http\Controllers\Inventario\EquipoCampoController;
use App\Http\Controllers\Inventario\EquipoHistorialController;
use App\Http\Controllers\Inventario\EquipoRelacionController;
use App\Http\Controllers\Inventario\EquipoUsuarioController;
use App\Http\Controllers\Inventario\TipoEquipoController;
use App\Http\Controllers\Inventario\TipoEquipoCampoController;
use App\Http\Controllers\Inventario\TipoRelacionEquipoController;
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

    // Inventario - Equipos
    Route::prefix('inventario')->name('inventario.')->group(function () {
        Route::get('/equipo', [EquipoController::class, 'index'])->name('equipo.index');
        Route::post('/equipo', [EquipoController::class, 'store'])->name('equipo.store');
        Route::get('/equipo/{id}/editar', [EquipoController::class, 'edit'])->name('equipo.edit');
        Route::put('/equipo/{id}', [EquipoController::class, 'update'])->name('equipo.update');
        Route::post('/equipo/toggle/{id}', [EquipoController::class, 'toggleActivo'])->name('equipo.toggle');
        Route::get('/equipo/campos/{idTipo}', [EquipoController::class, 'campos'])->name('equipo.campos');

        // Campos personalizados
        Route::get('/campo', [EquipoCampoController::class, 'index'])->name('campo.index');
        Route::post('/campo', [EquipoCampoController::class, 'store'])->name('campo.store');
        Route::put('/campo/{id}', [EquipoCampoController::class, 'update'])->name('campo.update');
        Route::post('/campo/toggle/{id}', [EquipoCampoController::class, 'toggleActivo'])->name('campo.toggle');

        // Tipos de equipo
        Route::get('/tipo-equipo', [TipoEquipoController::class, 'index'])->name('tipoEquipo.index');
        Route::post('/tipo-equipo', [TipoEquipoController::class, 'store'])->name('tipoEquipo.store');
        Route::put('/tipo-equipo/{id}', [TipoEquipoController::class, 'update'])->name('tipoEquipo.update');
        Route::post('/tipo-equipo/toggle/{id}', [TipoEquipoController::class, 'toggleActivo'])->name('tipoEquipo.toggle');

        // Tipo-Equipo-Campo (junction)
        Route::get('/tipo-equipo-campo', [TipoEquipoCampoController::class, 'index'])->name('tipoEquipoCampo.index');
        Route::post('/tipo-equipo-campo', [TipoEquipoCampoController::class, 'store'])->name('tipoEquipoCampo.store');
        Route::put('/tipo-equipo-campo/{id}', [TipoEquipoCampoController::class, 'update'])->name('tipoEquipoCampo.update');
        Route::post('/tipo-equipo-campo/toggle/{id}', [TipoEquipoCampoController::class, 'toggleActivo'])->name('tipoEquipoCampo.toggle');

        // Tipos de relación
        Route::get('/tipo-relacion', [TipoRelacionEquipoController::class, 'index'])->name('tipoRelacion.index');
        Route::post('/tipo-relacion', [TipoRelacionEquipoController::class, 'store'])->name('tipoRelacion.store');
        Route::put('/tipo-relacion/{id}', [TipoRelacionEquipoController::class, 'update'])->name('tipoRelacion.update');
        Route::post('/tipo-relacion/toggle/{id}', [TipoRelacionEquipoController::class, 'toggleActivo'])->name('tipoRelacion.toggle');

        // Relaciones padre-hijo
        Route::get('/relacion', [EquipoRelacionController::class, 'index'])->name('relacion.index');
        Route::post('/relacion', [EquipoRelacionController::class, 'store'])->name('relacion.store');
        Route::put('/relacion/{id}', [EquipoRelacionController::class, 'update'])->name('relacion.update');
        Route::post('/relacion/toggle/{id}', [EquipoRelacionController::class, 'toggleActivo'])->name('relacion.toggle');

        // Asignación a usuarios
        Route::get('/asignacion', [EquipoUsuarioController::class, 'index'])->name('asignacion.index');
        Route::post('/asignacion', [EquipoUsuarioController::class, 'store'])->name('asignacion.store');
        Route::put('/asignacion/{id}', [EquipoUsuarioController::class, 'update'])->name('asignacion.update');
        Route::post('/asignacion/toggle/{id}', [EquipoUsuarioController::class, 'toggleActivo'])->name('asignacion.toggle');
        Route::post('/asignacion/desvincular', [EquipoUsuarioController::class, 'desvincular'])->name('asignacion.desvincular');
        Route::post('/asignacion/desvincular-seleccionados', [EquipoUsuarioController::class, 'desvincularSeleccionados'])->name('asignacion.desvincularSeleccionados');

        // Historial
        Route::get('/historial', [EquipoHistorialController::class, 'index'])->name('historial.index');
    });

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
