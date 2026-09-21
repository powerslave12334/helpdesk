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
use App\Http\Controllers\Configuracion\RolPermisoController;
use App\Http\Controllers\Configuracion\PermisoUsuariosController;
use App\Http\Controllers\Configuracion\RolesUsuariosController;
use App\Http\Controllers\Configuracion\ConfiguracionGeneralController;
use App\Http\Controllers\Configuracion\SubModuloController;
use App\Http\Controllers\Configuracion\AccionController;
use App\Http\Controllers\Configuracion\OficinaController;
use App\Http\Controllers\Configuracion\EstatusController;
use App\Http\Controllers\Configuracion\ModuloController;
use App\Http\Controllers\Configuracion\AreaController;
use App\Http\Controllers\Configuracion\DeptoController;
use App\Http\Controllers\NotificacionesController;
use App\Http\Controllers\TicketConfig\TipoSoporteController;
use App\Http\Controllers\TicketConfig\TipoEquipoTicketController;
use App\Http\Controllers\TicketConfig\TipoDesarrolloController;
use App\Http\Controllers\TicketConfig\SistemasController;
use App\Http\Controllers\TicketConfig\ModuloSistemaController;
use App\Http\Controllers\TicketConfig\SubModuloSistemaController;
use App\Http\Controllers\TipoEmpleadoController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::post('/recuperar-password', [LoginController::class, 'recuperarPassword'])->name('login.recuperarPassword');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Tickets
    Route::middleware('permiso:ticket,Consultar')->group(function () {
        Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    });
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::post('/tickets/desarrollo', [TicketController::class, 'storeDesarrollo'])->name('tickets.storeDesarrollo');
    Route::get('/tickets/{id}', [TicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/comentario', [TicketController::class, 'addComment'])->name('tickets.addComment');
    Route::post('/tickets/evidencia', [TicketController::class, 'addEvidence'])->name('tickets.addEvidence');
    Route::post('/tickets/gestion', [TicketController::class, 'updateManagement'])->name('tickets.updateManagement');
    Route::post('/tickets/cerrar/{id}', [TicketController::class, 'closeWithApproval'])->name('tickets.close');
    Route::get('/tickets/modulos/{id}', [TicketController::class, 'obtenerModulos'])->name('tickets.modulos');
    Route::get('/tickets/submodulos/{id}', [TicketController::class, 'obtenerSubmodulos'])->name('tickets.submodulos');
    Route::get('/tickets/evidencia/{id}', [TicketController::class, 'viewEvidence'])->name('tickets.viewEvidence');

    // Empleados
    Route::middleware('permiso:empleado,Consultar')->group(function () {
        Route::get('/empleados', [EmpleadoController::class, 'index'])->name('empleados.index');
    });
    Route::post('/empleados', [EmpleadoController::class, 'store'])->name('empleados.store');
    Route::put('/empleados/{id}', [EmpleadoController::class, 'update'])->name('empleados.update');
    Route::post('/empleados/acceso', [EmpleadoController::class, 'guardarAcceso'])->name('empleados.acceso');
    Route::post('/empleados/restaurar-password/{id}', [EmpleadoController::class, 'restaurarPassword'])->name('empleados.restaurarPassword');
    Route::post('/empleados/toggle/{id}', [EmpleadoController::class, 'toggleActivo'])->name('empleados.toggle');

    // Inventario - Equipos
    Route::prefix('inventario')->name('inventario.')->middleware('permiso:equipo,Consultar')->group(function () {
        Route::get('/equipo', [EquipoController::class, 'index'])->name('equipo.index');
        Route::get('/equipo/crear', [EquipoController::class, 'create'])->name('equipo.create');
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
        Route::get('/asignacion/acta/{id}', [EquipoUsuarioController::class, 'generarActa'])->name('asignacion.acta');

        // Historial
        Route::get('/historial', [EquipoHistorialController::class, 'index'])->name('historial.index');
    });

    // Configuración
    Route::prefix('configuracion')->name('configuracion.')->middleware('permiso:rol,Consultar')->group(function () {
        // Roles
        Route::get('/roles', [RolController::class, 'index'])->name('roles.index');
        Route::post('/roles', [RolController::class, 'store'])->name('roles.store');
        Route::put('/roles/{id}', [RolController::class, 'update'])->name('roles.update');
        Route::post('/roles/toggle/{id}', [RolController::class, 'toggleActivo'])->name('roles.toggle');

        // Permisos por Rol
        Route::get('/rol-permisos', [RolPermisoController::class, 'index'])->name('rolPermiso.index');
        Route::post('/rol-permisos/guardar-matriz', [RolPermisoController::class, 'guardarMatriz'])->name('rolPermiso.guardarMatriz');

        // Permisos por Usuario
        Route::get('/permiso-usuarios', [PermisoUsuariosController::class, 'index'])->name('permisoUsuarios.index');
        Route::post('/permiso-usuarios/guardar-matriz', [PermisoUsuariosController::class, 'guardarMatriz'])->name('permisoUsuarios.guardarMatriz');

        // Asignación de Roles a Usuarios
        Route::get('/roles-usuarios', [RolesUsuariosController::class, 'index'])->name('rolesUsuarios.index');
        Route::post('/roles-usuarios', [RolesUsuariosController::class, 'store'])->name('rolesUsuarios.store');
        Route::post('/roles-usuarios/toggle/{id}', [RolesUsuariosController::class, 'toggleActivo'])->name('rolesUsuarios.toggle');

        // Configuración General
        Route::get('/config-general', [ConfiguracionGeneralController::class, 'index'])->name('configGeneral.index');
        Route::post('/config-general', [ConfiguracionGeneralController::class, 'store'])->name('configGeneral.store');
        Route::put('/config-general/{id}', [ConfiguracionGeneralController::class, 'update'])->name('configGeneral.update');
        Route::post('/config-general/toggle/{id}', [ConfiguracionGeneralController::class, 'toggleActivo'])->name('configGeneral.toggle');

        // Estatus
        Route::get('/estatus', [EstatusController::class, 'index'])->name('estatus.index');
        Route::post('/estatus', [EstatusController::class, 'store'])->name('estatus.store');
        Route::put('/estatus/{id}', [EstatusController::class, 'update'])->name('estatus.update');
        Route::post('/estatus/toggle/{id}', [EstatusController::class, 'toggleActivo'])->name('estatus.toggle');

        // Módulos
        Route::get('/modulos', [ModuloController::class, 'index'])->name('modulos.index');
        Route::post('/modulos', [ModuloController::class, 'store'])->name('modulos.store');
        Route::put('/modulos/{id}', [ModuloController::class, 'update'])->name('modulos.update');

        // SubMódulos
        Route::get('/submodulos', [SubModuloController::class, 'index'])->name('submodulos.index');
        Route::post('/submodulos', [SubModuloController::class, 'store'])->name('submodulos.store');
        Route::put('/submodulos/{id}', [SubModuloController::class, 'update'])->name('submodulos.update');
        Route::post('/submodulos/toggle/{id}', [SubModuloController::class, 'toggleActivo'])->name('submodulos.toggle');

        // Acciones
        Route::get('/acciones', [AccionController::class, 'index'])->name('acciones.index');
        Route::post('/acciones', [AccionController::class, 'store'])->name('acciones.store');
        Route::put('/acciones/{id}', [AccionController::class, 'update'])->name('acciones.update');
        Route::post('/acciones/toggle/{id}', [AccionController::class, 'toggleActivo'])->name('acciones.toggle');

        // Áreas
        Route::get('/areas', [AreaController::class, 'index'])->name('areas.index');
        Route::post('/areas', [AreaController::class, 'store'])->name('areas.store');
        Route::put('/areas/{id}', [AreaController::class, 'update'])->name('areas.update');
        Route::post('/areas/toggle/{id}', [AreaController::class, 'toggleActivo'])->name('areas.toggle');

        // Departamentos
        Route::get('/deptos', [DeptoController::class, 'index'])->name('deptos.index');
        Route::post('/deptos', [DeptoController::class, 'store'])->name('deptos.store');
        Route::put('/deptos/{id}', [DeptoController::class, 'update'])->name('deptos.update');
        Route::post('/deptos/toggle/{id}', [DeptoController::class, 'toggleActivo'])->name('deptos.toggle');

        // Oficinas
        Route::get('/oficinas', [OficinaController::class, 'index'])->name('oficinas.index');
        Route::post('/oficinas', [OficinaController::class, 'store'])->name('oficinas.store');
        Route::put('/oficinas/{id}', [OficinaController::class, 'update'])->name('oficinas.update');
        Route::post('/oficinas/toggle/{id}', [OficinaController::class, 'toggleActivo'])->name('oficinas.toggle');
    });

    // Empleados - Tipo Empleado
    Route::get('/empleados/tipo-empleado', [TipoEmpleadoController::class, 'index'])->name('empleados.tipoEmpleado.index');
    Route::post('/empleados/tipo-empleado', [TipoEmpleadoController::class, 'store'])->name('empleados.tipoEmpleado.store');
    Route::put('/empleados/tipo-empleado/{id}', [TipoEmpleadoController::class, 'update'])->name('empleados.tipoEmpleado.update');
    Route::post('/empleados/tipo-empleado/toggle/{id}', [TipoEmpleadoController::class, 'toggleActivo'])->name('empleados.tipoEmpleado.toggle');

    // Ticket Config
    Route::prefix('ticketconfig')->name('ticketconfig.')->middleware('permiso:tipoSoporte,Consultar')->group(function () {
        // Tipos de Soporte
        Route::get('/tipos-soporte', [TipoSoporteController::class, 'index'])->name('tipossoporte.index');
        Route::post('/tipos-soporte', [TipoSoporteController::class, 'store'])->name('tipossoporte.store');
        Route::put('/tipos-soporte/{id}', [TipoSoporteController::class, 'update'])->name('tipossoporte.update');
        Route::post('/tipos-soporte/toggle/{id}', [TipoSoporteController::class, 'toggleActivo'])->name('tipossoporte.toggle');

        // Tipos Equipo Ticket
        Route::get('/tipos-equipo-ticket', [TipoEquipoTicketController::class, 'index'])->name('tiposequipoticket.index');
        Route::post('/tipos-equipo-ticket', [TipoEquipoTicketController::class, 'store'])->name('tiposequipoticket.store');
        Route::put('/tipos-equipo-ticket/{id}', [TipoEquipoTicketController::class, 'update'])->name('tiposequipoticket.update');
        Route::post('/tipos-equipo-ticket/toggle/{id}', [TipoEquipoTicketController::class, 'toggleActivo'])->name('tiposequipoticket.toggle');

        // Tipos Desarrollo
        Route::get('/tipos-desarrollo', [TipoDesarrolloController::class, 'index'])->name('tiposdesarrollo.index');
        Route::post('/tipos-desarrollo', [TipoDesarrolloController::class, 'store'])->name('tiposdesarrollo.store');
        Route::put('/tipos-desarrollo/{id}', [TipoDesarrolloController::class, 'update'])->name('tiposdesarrollo.update');
        Route::post('/tipos-desarrollo/toggle/{id}', [TipoDesarrolloController::class, 'toggleActivo'])->name('tiposdesarrollo.toggle');

        // Sistemas
        Route::get('/sistemas', [SistemasController::class, 'index'])->name('sistemas.index');
        Route::post('/sistemas', [SistemasController::class, 'store'])->name('sistemas.store');
        Route::put('/sistemas/{id}', [SistemasController::class, 'update'])->name('sistemas.update');
        Route::post('/sistemas/toggle/{id}', [SistemasController::class, 'toggleActivo'])->name('sistemas.toggle');

        // Módulos Sistemas
        Route::get('/modulos-sistemas', [ModuloSistemaController::class, 'index'])->name('modulossistemas.index');
        Route::post('/modulos-sistemas', [ModuloSistemaController::class, 'store'])->name('modulossistemas.store');
        Route::put('/modulos-sistemas/{id}', [ModuloSistemaController::class, 'update'])->name('modulossistemas.update');
        Route::post('/modulos-sistemas/toggle/{id}', [ModuloSistemaController::class, 'toggleActivo'])->name('modulossistemas.toggle');

        // SubMódulos Sistemas
        Route::get('/submodulos-sistemas', [SubModuloSistemaController::class, 'index'])->name('submodulossistemas.index');
        Route::post('/submodulos-sistemas', [SubModuloSistemaController::class, 'store'])->name('submodulossistemas.store');
        Route::put('/submodulos-sistemas/{id}', [SubModuloSistemaController::class, 'update'])->name('submodulossistemas.update');
        Route::post('/submodulos-sistemas/toggle/{id}', [SubModuloSistemaController::class, 'toggleActivo'])->name('submodulossistemas.toggle');
    });

    // Notificaciones API
    Route::prefix('api/notificaciones')->name('api.notificaciones.')->group(function () {
        Route::get('/', [NotificacionesController::class, 'obtener'])->name('obtener');
        Route::post('/marcar-leido', [NotificacionesController::class, 'marcarLeido'])->name('marcarLeido');
        Route::post('/marcar-todas-leido', [NotificacionesController::class, 'marcarTodasLeido'])->name('marcarTodasLeido');
    });
});
