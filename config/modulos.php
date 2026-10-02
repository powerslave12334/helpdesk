<?php

/*
 * Catálogo de módulos y submódulos del HelpDesk — fuente única de verdad.
 *
 * Para agregar un módulo o submódulo nuevo:
 *  1. Agregar su entrada aquí (clave, ruta, icono, orden).
 *  2. Declarar sus rutas en routes/web.php con el middleware permiso:<clave>
 *     y su enlace en el sidebar (este menú se genera desde este archivo).
 *  3. Ejecutar `php artisan modulos:sync` para reflejarlo en tblModulos /
 *     tblSubModulos, que solo sirven de almacén de permisos por rol
 *     (la migración lo hace automáticamente en deploy).
 *
 * No existen formularios para crear, editar ni eliminar módulos ni submódulos.
 */

return [

    'modulos' => [
        [
            'id' => 6,
            'nombre' => 'Dashboard',
            'orden' => 1,
            'icono' => 'fas fa-tachometer-alt',
            'enlace' => 'dashboard',
        ],
        [
            'id' => 1,
            'nombre' => 'Tickets',
            'orden' => 2,
            'icono' => 'fas fa-ticket',
            'patrones' => [
                'tickets.*',
                'ticketconfig.tipossoporte.*',
                'ticketconfig.tiposequipoticket.*',
                'ticketconfig.tiposdesarrollo.*',
                'ticketconfig.sistemas.*',
            ],
        ],
        [
            'id' => 2,
            'nombre' => 'Inventario',
            'orden' => 4,
            'icono' => 'fas fa-boxes-stacked',
            'patrones' => [
                'inventario.*',
            ],
        ],
        [
            'id' => 3,
            'nombre' => 'Empleados',
            'orden' => 5,
            'icono' => 'fas fa-users',
            'patrones' => [
                'empleados.*',
                'configuracion.deptos.*',
                'configuracion.areas.*',
                'configuracion.oficinas.*',
            ],
        ],
        [
            'id' => 5,
            'nombre' => 'Configuración',
            'orden' => 6,
            'icono' => 'fas fa-cog',
            'patrones' => [
                'configuracion.*',
                'ticketconfig.modulossistemas.*',
                'ticketconfig.submodulossistemas.*',
            ],
        ],
    ],

    'submodulos' => [
        // --- Tickets (módulo 1) ---
        ['clave' => 'tickets', 'id_Modulos' => 1, 'nombre' => 'Tickets', 'icono' => 'fas fa-ticket', 'ruta' => 'tickets.index', 'activa' => 'tickets.index', 'orden' => 1],
        ['clave' => 'tipo-soporte', 'id_Modulos' => 1, 'nombre' => 'Tipo Soporte', 'icono' => 'fas fa-headset', 'ruta' => 'ticketconfig.tipossoporte.index', 'activa' => 'ticketconfig.tipossoporte.*', 'orden' => 2],
        ['clave' => 'tipo-equipo-soporte', 'id_Modulos' => 1, 'nombre' => 'Tipo Equipo Soporte', 'icono' => 'fas fa-laptop', 'ruta' => 'ticketconfig.tiposequipoticket.index', 'activa' => 'ticketconfig.tiposequipoticket.*', 'orden' => 4],
        ['clave' => 'tipo-desarrollo', 'id_Modulos' => 1, 'nombre' => 'Tipo de Desarrollo', 'icono' => 'fas fa-code', 'ruta' => 'ticketconfig.tiposdesarrollo.index', 'activa' => 'ticketconfig.tiposdesarrollo.*', 'orden' => 5],
        ['clave' => 'tipo-sistema', 'id_Modulos' => 1, 'nombre' => 'Tipo de Sistema', 'icono' => 'fas fa-desktop', 'ruta' => 'ticketconfig.sistemas.index', 'activa' => 'ticketconfig.sistemas.*', 'orden' => 6],

        // --- Inventario (módulo 2) ---
        ['clave' => 'tipo-equipo', 'id_Modulos' => 2, 'nombre' => 'Tipo Inventario', 'icono' => 'fas fa-tags', 'ruta' => 'inventario.tipoEquipo.index', 'activa' => 'inventario.tipoEquipo.*', 'orden' => 1],
        ['clave' => 'equipo', 'id_Modulos' => 2, 'nombre' => 'Equipo', 'icono' => 'fas fa-laptop', 'ruta' => 'inventario.equipo.index', 'activa' => 'inventario.equipo.*', 'orden' => 2],
        ['clave' => 'asignacion-equipo', 'id_Modulos' => 2, 'nombre' => 'Asignación de Equipo', 'icono' => 'fas fa-user-tag', 'ruta' => 'inventario.asignacion.index', 'activa' => 'inventario.asignacion.*', 'orden' => 3],
        ['clave' => 'tipo-relacion-equipo', 'id_Modulos' => 2, 'nombre' => 'Tipo Relación Equipo', 'icono' => 'fas fa-link', 'ruta' => 'inventario.tipoRelacion.index', 'activa' => 'inventario.tipoRelacion.*', 'orden' => 6],
        ['clave' => 'relacion-equipo', 'id_Modulos' => 2, 'nombre' => 'Relación Equipo', 'icono' => 'fas fa-project-diagram', 'ruta' => 'inventario.relacion.index', 'activa' => 'inventario.relacion.*', 'orden' => 7],
        ['clave' => 'historial-asignacion', 'id_Modulos' => 2, 'nombre' => 'Historial de Asignación', 'icono' => 'fas fa-history', 'ruta' => 'inventario.historial.index', 'activa' => 'inventario.historial.*', 'orden' => 8],
        ['clave' => 'mantenimientos', 'id_Modulos' => 2, 'nombre' => 'Mantenimientos', 'icono' => 'fas fa-calendar-alt', 'ruta' => 'inventario.mantenimientos.index', 'activa' => 'inventario.mantenimientos.*', 'orden' => 0],

        // --- Empleados (módulo 3) ---
        ['clave' => 'ver-empleados', 'id_Modulos' => 3, 'nombre' => 'Ver Empleados', 'icono' => 'fas fa-eye', 'ruta' => 'empleados.index', 'activa' => 'empleados.index', 'orden' => 2],
        ['clave' => 'departamentos', 'id_Modulos' => 3, 'nombre' => 'Departamentos', 'icono' => 'fas fa-sitemap', 'ruta' => 'configuracion.deptos.index', 'activa' => 'configuracion.deptos.*', 'orden' => 3],
        ['clave' => 'areas', 'id_Modulos' => 3, 'nombre' => 'Áreas', 'icono' => 'fas fa-building', 'ruta' => 'configuracion.areas.index', 'activa' => 'configuracion.areas.*', 'orden' => 4],
        ['clave' => 'oficinas', 'id_Modulos' => 3, 'nombre' => 'Oficinas', 'icono' => 'fas fa-map-marker-alt', 'ruta' => 'configuracion.oficinas.index', 'activa' => 'configuracion.oficinas.*', 'orden' => 5],
        ['clave' => 'tipo-empleado', 'id_Modulos' => 3, 'nombre' => 'Tipo de Empleado', 'icono' => 'fas fa-id-card', 'ruta' => 'empleados.tipoEmpleado.index', 'activa' => 'empleados.tipoEmpleado.*', 'orden' => 6],

        // --- Configuración (módulo 5) ---
        ['clave' => 'config-general', 'id_Modulos' => 5, 'nombre' => 'Configuración General', 'icono' => 'fas fa-cog', 'ruta' => 'configuracion.configGeneral.index', 'activa' => 'configuracion.configGeneral.*', 'orden' => 0],
        ['clave' => 'roles', 'id_Modulos' => 5, 'nombre' => 'Roles', 'icono' => 'fas fa-user-shield', 'ruta' => 'configuracion.roles.index', 'activa' => 'configuracion.roles.*', 'orden' => 4],
        ['clave' => 'roles-modulos', 'id_Modulos' => 5, 'nombre' => 'Módulos por Rol', 'icono' => 'fas fa-toggle-on', 'ruta' => 'configuracion.rolModulo.index', 'activa' => 'configuracion.rolModulo.*', 'orden' => 0],
        ['clave' => 'estatus', 'id_Modulos' => 5, 'nombre' => 'Estatus', 'icono' => 'fas fa-list-check', 'ruta' => 'configuracion.estatus.index', 'activa' => 'configuracion.estatus.*', 'orden' => 8],
        ['clave' => 'modulos-sistemas', 'id_Modulos' => 5, 'nombre' => 'Módulos Sistemas', 'icono' => 'fas fa-cubes', 'ruta' => 'ticketconfig.modulossistemas.index', 'activa' => 'ticketconfig.modulossistemas.*', 'orden' => 10],
        ['clave' => 'submodulos-sistemas', 'id_Modulos' => 5, 'nombre' => 'Submódulos Sistemas', 'icono' => 'fas fa-cube', 'ruta' => 'ticketconfig.submodulossistemas.index', 'activa' => 'ticketconfig.submodulossistemas.*', 'orden' => 11],
    ],

];
