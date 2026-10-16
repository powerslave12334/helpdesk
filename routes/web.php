<?php

use App\Http\Controllers\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\Inventario\EquipoController;
use App\Http\Controllers\Inventario\EquipoHistorialController;
use App\Http\Controllers\Inventario\EquipoRelacionController;
use App\Http\Controllers\Inventario\EquipoUsuarioController;
use App\Http\Controllers\Inventario\TipoEquipoController;
use App\Http\Controllers\Inventario\TipoRelacionEquipoController;
use App\Http\Controllers\Inventario\VehiculoController;
use App\Http\Controllers\Configuracion\CatalogoEquipoController;
use App\Http\Controllers\Configuracion\RolController;
use App\Http\Controllers\Configuracion\RolModuloController;

use App\Http\Controllers\Configuracion\ConfiguracionGeneralController;
use App\Http\Controllers\Configuracion\OficinaController;
use App\Http\Controllers\Configuracion\EstatusController;
use App\Http\Controllers\Configuracion\AreaController;
use App\Http\Controllers\Configuracion\DeptoController;
use App\Http\Controllers\NotificacionesController;
use App\Http\Controllers\TicketConfig\TipoSoporteController;
use App\Http\Controllers\TicketConfig\TipoEquipoTicketController;
use App\Http\Controllers\TicketConfig\TipoDesarrolloController;
use App\Http\Controllers\TicketConfig\SistemasController;
use App\Http\Controllers\TicketConfig\ModuloSistemaController;
use App\Http\Controllers\TicketConfig\SubModuloSistemaController;
use App\Http\Controllers\Inventario\MantenimientoController;
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
    Route::middleware('permiso:tickets')->group(function () {
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
        Route::get('/tickets/evidencia/{id}', [TicketController::class, 'viewEvidence'])->name('tickets.viewEvidence');
    });

    // Empleados
    Route::middleware('permiso:ver-empleados')->group(function () {
        Route::get('/empleados', [EmpleadoController::class, 'index'])->name('empleados.index');
        Route::post('/empleados', [EmpleadoController::class, 'store'])->name('empleados.store');
        Route::put('/empleados/{id}', [EmpleadoController::class, 'update'])->name('empleados.update');
        Route::post('/empleados/acceso', [EmpleadoController::class, 'guardarAcceso'])->name('empleados.acceso');
        Route::post('/empleados/restaurar-password/{id}', [EmpleadoController::class, 'restaurarPassword'])->name('empleados.restaurarPassword');
        Route::post('/empleados/toggle/{id}', [EmpleadoController::class, 'toggleActivo'])->name('empleados.toggle');
    });

    // Empleados - Tipo Empleado
    Route::middleware('permiso:tipo-empleado')->group(function () {
        Route::get('/empleados/tipo-empleado', [TipoEmpleadoController::class, 'index'])->name('empleados.tipoEmpleado.index');
        Route::post('/empleados/tipo-empleado', [TipoEmpleadoController::class, 'store'])->name('empleados.tipoEmpleado.store');
        Route::put('/empleados/tipo-empleado/{id}', [TipoEmpleadoController::class, 'update'])->name('empleados.tipoEmpleado.update');
        Route::post('/empleados/tipo-empleado/toggle/{id}', [TipoEmpleadoController::class, 'toggleActivo'])->name('empleados.tipoEmpleado.toggle');
    });

    // Inventario
    Route::prefix('inventario')->name('inventario.')->group(function () {
        // Equipo
        Route::middleware('permiso:equipo')->group(function () {
            Route::get('/equipo', [EquipoController::class, 'index'])->name('equipo.index');
            Route::get('/equipo/tipos', [EquipoController::class, 'seleccionarTipo'])->name('equipo.tipos');
            Route::get('/equipo/crear/{tipo?}', [EquipoController::class, 'create'])->name('equipo.create');
            Route::post('/equipo', [EquipoController::class, 'store'])->name('equipo.store');
            Route::get('/equipo/{id}/editar', [EquipoController::class, 'edit'])->name('equipo.edit');
            Route::put('/equipo/{id}', [EquipoController::class, 'update'])->name('equipo.update');
            Route::post('/equipo/toggle/{id}', [EquipoController::class, 'toggleActivo'])->name('equipo.toggle');
        });

        // Tipos de equipo
        Route::middleware('permiso:tipo-equipo')->group(function () {
            Route::get('/tipo-equipo', [TipoEquipoController::class, 'index'])->name('tipoEquipo.index');
            Route::post('/tipo-equipo', [TipoEquipoController::class, 'store'])->name('tipoEquipo.store');
            Route::put('/tipo-equipo/{id}', [TipoEquipoController::class, 'update'])->name('tipoEquipo.update');
            Route::post('/tipo-equipo/toggle/{id}', [TipoEquipoController::class, 'toggleActivo'])->name('tipoEquipo.toggle');
        });

        // Vehículos (unidades GPS)
        Route::middleware('permiso:vehiculos')->group(function () {
            Route::get('/vehiculos', [VehiculoController::class, 'index'])->name('vehiculo.index');
            Route::post('/vehiculos', [VehiculoController::class, 'store'])->name('vehiculo.store');
            Route::put('/vehiculos/{id}', [VehiculoController::class, 'update'])->name('vehiculo.update');
            Route::post('/vehiculos/toggle/{id}', [VehiculoController::class, 'toggleActivo'])->name('vehiculo.toggle');
        });

        // Tipos de relación
        Route::middleware('permiso:tipo-relacion-equipo')->group(function () {
            Route::get('/tipo-relacion', [TipoRelacionEquipoController::class, 'index'])->name('tipoRelacion.index');
            Route::post('/tipo-relacion', [TipoRelacionEquipoController::class, 'store'])->name('tipoRelacion.store');
            Route::put('/tipo-relacion/{id}', [TipoRelacionEquipoController::class, 'update'])->name('tipoRelacion.update');
            Route::post('/tipo-relacion/toggle/{id}', [TipoRelacionEquipoController::class, 'toggleActivo'])->name('tipoRelacion.toggle');
        });

        // Relaciones padre-hijo
        Route::middleware('permiso:relacion-equipo')->group(function () {
            Route::get('/relacion', [EquipoRelacionController::class, 'index'])->name('relacion.index');
            Route::post('/relacion', [EquipoRelacionController::class, 'store'])->name('relacion.store');
            Route::put('/relacion/{id}', [EquipoRelacionController::class, 'update'])->name('relacion.update');
            Route::post('/relacion/toggle/{id}', [EquipoRelacionController::class, 'toggleActivo'])->name('relacion.toggle');
        });

        // Asignación a usuarios
        Route::middleware('permiso:asignacion-equipo')->group(function () {
            Route::get('/asignacion', [EquipoUsuarioController::class, 'index'])->name('asignacion.index');
            Route::post('/asignacion', [EquipoUsuarioController::class, 'store'])->name('asignacion.store');
            Route::put('/asignacion/{id}', [EquipoUsuarioController::class, 'update'])->name('asignacion.update');
            Route::post('/asignacion/toggle/{id}', [EquipoUsuarioController::class, 'toggleActivo'])->name('asignacion.toggle');
            Route::post('/asignacion/desvincular', [EquipoUsuarioController::class, 'desvincular'])->name('asignacion.desvincular');
            Route::post('/asignacion/desvincular-seleccionados', [EquipoUsuarioController::class, 'desvincularSeleccionados'])->name('asignacion.desvincularSeleccionados');
            Route::get('/asignacion/acta/{id}', [EquipoUsuarioController::class, 'generarActa'])->name('asignacion.acta');
        });

        // Historial
        Route::middleware('permiso:historial-asignacion')->group(function () {
            Route::get('/historial', [EquipoHistorialController::class, 'index'])->name('historial.index');
        });

        // Mantenimientos
        Route::middleware('permiso:mantenimientos')->group(function () {
            Route::get('/mantenimientos', [MantenimientoController::class, 'index'])->name('mantenimientos.index');
            Route::get('/mantenimientos/api', [MantenimientoController::class, 'api'])->name('mantenimientos.api');
            Route::post('/mantenimientos', [MantenimientoController::class, 'store'])->name('mantenimientos.store');
            Route::post('/mantenimientos/{id}/completar', [MantenimientoController::class, 'completar'])->name('mantenimientos.completar');
            Route::post('/mantenimientos/{id}/cambiar-fecha', [MantenimientoController::class, 'cambiarFecha'])->name('mantenimientos.cambiarFecha');
            Route::delete('/mantenimientos/{id}', [MantenimientoController::class, 'destroy'])->name('mantenimientos.destroy');
            Route::get('/mantenimientos/buscar', [MantenimientoController::class, 'buscar'])->name('mantenimientos.buscar');
            Route::get('/mantenimientos/buscar-equipo', [MantenimientoController::class, 'buscarEquipo'])->name('mantenimientos.buscar-equipo');
            Route::get('/mantenimientos/{id}/editar-reporte', [MantenimientoController::class, 'editarReporte'])->name('mantenimientos.editar-reporte');
            Route::post('/mantenimientos/{id}/guardar-reporte', [MantenimientoController::class, 'guardarReporte'])->name('mantenimientos.guardar-reporte');
            Route::post('/mantenimientos/{id}/cerrar-reporte', [MantenimientoController::class, 'cerrarReporte'])->name('mantenimientos.cerrar-reporte');
            Route::get('/mantenimientos/{id}/reporte', [MantenimientoController::class, 'generarReporte'])->name('mantenimientos.reporte');
        });
    });

    // Configuración
    Route::prefix('configuracion')->name('configuracion.')->group(function () {
        // Roles
        Route::middleware('permiso:roles')->group(function () {
            Route::get('/roles', [RolController::class, 'index'])->name('roles.index');
            Route::post('/roles', [RolController::class, 'store'])->name('roles.store');
            Route::put('/roles/{id}', [RolController::class, 'update'])->name('roles.update');
            Route::post('/roles/toggle/{id}', [RolController::class, 'toggleActivo'])->name('roles.toggle');
        });

        // Módulos y submódulos por Rol
        Route::middleware('permiso:roles-modulos')->group(function () {
            Route::get('/roles-modulos', [RolModuloController::class, 'index'])->name('rolModulo.index');
            Route::post('/roles-modulos', [RolModuloController::class, 'guardar'])->name('rolModulo.guardar');
        });

        // Configuración General
        Route::middleware('permiso:config-general')->group(function () {
            Route::get('/config-general', [ConfiguracionGeneralController::class, 'index'])->name('configGeneral.index');
            Route::post('/config-general', [ConfiguracionGeneralController::class, 'store'])->name('configGeneral.store');
            Route::put('/config-general/{id}', [ConfiguracionGeneralController::class, 'update'])->name('configGeneral.update');
            Route::post('/config-general/toggle/{id}', [ConfiguracionGeneralController::class, 'toggleActivo'])->name('configGeneral.toggle');
        });

        // Estatus
        Route::middleware('permiso:estatus')->group(function () {
            Route::get('/estatus', [EstatusController::class, 'index'])->name('estatus.index');
            Route::post('/estatus', [EstatusController::class, 'store'])->name('estatus.store');
            Route::put('/estatus/{id}', [EstatusController::class, 'update'])->name('estatus.update');
            Route::post('/estatus/toggle/{id}', [EstatusController::class, 'toggleActivo'])->name('estatus.toggle');
        });

        // Áreas
        Route::middleware('permiso:areas')->group(function () {
            Route::get('/areas', [AreaController::class, 'index'])->name('areas.index');
            Route::post('/areas', [AreaController::class, 'store'])->name('areas.store');
            Route::put('/areas/{id}', [AreaController::class, 'update'])->name('areas.update');
            Route::post('/areas/toggle/{id}', [AreaController::class, 'toggleActivo'])->name('areas.toggle');
        });

        // Departamentos
        Route::middleware('permiso:departamentos')->group(function () {
            Route::get('/deptos', [DeptoController::class, 'index'])->name('deptos.index');
            Route::post('/deptos', [DeptoController::class, 'store'])->name('deptos.store');
            Route::put('/deptos/{id}', [DeptoController::class, 'update'])->name('deptos.update');
            Route::post('/deptos/toggle/{id}', [DeptoController::class, 'toggleActivo'])->name('deptos.toggle');
        });

        // Oficinas
        Route::middleware('permiso:oficinas')->group(function () {
            Route::get('/oficinas', [OficinaController::class, 'index'])->name('oficinas.index');
            Route::post('/oficinas', [OficinaController::class, 'store'])->name('oficinas.store');
            Route::put('/oficinas/{id}', [OficinaController::class, 'update'])->name('oficinas.update');
            Route::post('/oficinas/toggle/{id}', [OficinaController::class, 'toggleActivo'])->name('oficinas.toggle');
        });

        // Catálogos de Equipo (marcas, modelos, procesadores, SO, almacenamiento)
        Route::middleware('permiso:catalogo-equipo')->group(function () {
            Route::get('/catalogo-equipo/{categoria?}', [CatalogoEquipoController::class, 'index'])->name('catalogoEquipo.index');
            Route::post('/catalogo-equipo/{categoria}', [CatalogoEquipoController::class, 'store'])->name('catalogoEquipo.store');
            Route::put('/catalogo-equipo/{categoria}/{id}', [CatalogoEquipoController::class, 'update'])->name('catalogoEquipo.update');
            Route::post('/catalogo-equipo/{categoria}/toggle/{id}', [CatalogoEquipoController::class, 'toggle'])->name('catalogoEquipo.toggle');
        });
    });

    // Ticket Config
    Route::prefix('ticketconfig')->name('ticketconfig.')->group(function () {
        // Tipos de Soporte
        Route::middleware('permiso:tipo-soporte')->group(function () {
            Route::get('/tipos-soporte', [TipoSoporteController::class, 'index'])->name('tipossoporte.index');
            Route::post('/tipos-soporte', [TipoSoporteController::class, 'store'])->name('tipossoporte.store');
            Route::put('/tipos-soporte/{id}', [TipoSoporteController::class, 'update'])->name('tipossoporte.update');
            Route::post('/tipos-soporte/toggle/{id}', [TipoSoporteController::class, 'toggleActivo'])->name('tipossoporte.toggle');
        });

        // Tipos Equipo Ticket
        Route::middleware('permiso:tipo-equipo-soporte')->group(function () {
            Route::get('/tipos-equipo-ticket', [TipoEquipoTicketController::class, 'index'])->name('tiposequipoticket.index');
            Route::post('/tipos-equipo-ticket', [TipoEquipoTicketController::class, 'store'])->name('tiposequipoticket.store');
            Route::put('/tipos-equipo-ticket/{id}', [TipoEquipoTicketController::class, 'update'])->name('tiposequipoticket.update');
            Route::post('/tipos-equipo-ticket/toggle/{id}', [TipoEquipoTicketController::class, 'toggleActivo'])->name('tiposequipoticket.toggle');
        });

        // Tipos Desarrollo
        Route::middleware('permiso:tipo-desarrollo')->group(function () {
            Route::get('/tipos-desarrollo', [TipoDesarrolloController::class, 'index'])->name('tiposdesarrollo.index');
            Route::post('/tipos-desarrollo', [TipoDesarrolloController::class, 'store'])->name('tiposdesarrollo.store');
            Route::put('/tipos-desarrollo/{id}', [TipoDesarrolloController::class, 'update'])->name('tiposdesarrollo.update');
            Route::post('/tipos-desarrollo/toggle/{id}', [TipoDesarrolloController::class, 'toggleActivo'])->name('tiposdesarrollo.toggle');
        });

        // Sistemas
        Route::middleware('permiso:tipo-sistema')->group(function () {
            Route::get('/sistemas', [SistemasController::class, 'index'])->name('sistemas.index');
            Route::post('/sistemas', [SistemasController::class, 'store'])->name('sistemas.store');
            Route::put('/sistemas/{id}', [SistemasController::class, 'update'])->name('sistemas.update');
            Route::post('/sistemas/toggle/{id}', [SistemasController::class, 'toggleActivo'])->name('sistemas.toggle');
        });

        // Módulos Sistemas
        Route::middleware('permiso:modulos-sistemas')->group(function () {
            Route::get('/modulos-sistemas', [ModuloSistemaController::class, 'index'])->name('modulossistemas.index');
            Route::post('/modulos-sistemas', [ModuloSistemaController::class, 'store'])->name('modulossistemas.store');
            Route::put('/modulos-sistemas/{id}', [ModuloSistemaController::class, 'update'])->name('modulossistemas.update');
            Route::post('/modulos-sistemas/toggle/{id}', [ModuloSistemaController::class, 'toggleActivo'])->name('modulossistemas.toggle');
        });

        // SubMódulos Sistemas
        Route::middleware('permiso:submodulos-sistemas')->group(function () {
            Route::get('/submodulos-sistemas', [SubModuloSistemaController::class, 'index'])->name('submodulossistemas.index');
            Route::post('/submodulos-sistemas', [SubModuloSistemaController::class, 'store'])->name('submodulossistemas.store');
            Route::put('/submodulos-sistemas/{id}', [SubModuloSistemaController::class, 'update'])->name('submodulossistemas.update');
            Route::post('/submodulos-sistemas/toggle/{id}', [SubModuloSistemaController::class, 'toggleActivo'])->name('submodulossistemas.toggle');
        });
    });

    // Notificaciones API
    Route::prefix('api/notificaciones')->name('api.notificaciones.')->group(function () {
        Route::get('/', [NotificacionesController::class, 'obtener'])->name('obtener');
        Route::post('/marcar-leido', [NotificacionesController::class, 'marcarLeido'])->name('marcarLeido');
        Route::post('/marcar-todas-leido', [NotificacionesController::class, 'marcarTodasLeido'])->name('marcarTodasLeido');
    });
});
