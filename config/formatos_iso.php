<?php

use App\Http\Controllers\Iso9001\ActaEntregaController;
use App\Http\Controllers\Iso9001\InventarioHerramientaController;
use App\Http\Controllers\Iso9001\RecepcionEquipoController;
use App\Http\Controllers\Iso9001\ResponsivaErpController;

/*
 * Catálogo de formatos ISO 9001 (FO-IT) del HelpDesk — fuente única de verdad.
 *
 * Para agregar un formato nuevo:
 *  1. Agregar su entrada aquí (clave, nombre, icono, orden) con la clave
 *     "controlador" apuntando a su clase en app/Http/Controllers/Iso9001/.
 *  2. Crear su controlador (métodos index/create/store/pdf/destroy) y sus
 *     vistas en resources/views/iso9001/formatos/.
 *  3. Crear su tabla y modelo (un formato = una tabla, patrón del módulo).
 *
 * El panel (iso9001.index) y las rutas (iso9001.documentos / create / store /
 * pdf / destroy) consultan este archivo, por lo que no hay que tocar el menú
 * ni las rutas para habilitar un formato adicional.
 */

return [

    'formatos' => [
        [
            'clave' => 'FO-IT-01',
            'nombre' => 'Responsiva ERP',
            'icono' => 'fas fa-file-signature',
            'orden' => 1,
            'controlador' => ResponsivaErpController::class,
        ],
        [
            'clave' => 'FO-IT-02',
            'nombre' => 'Acta de entrega',
            'icono' => 'fas fa-hand-holding',
            'orden' => 2,
            'controlador' => ActaEntregaController::class,
        ],
        [
            'clave' => 'FO-IT-03',
            'nombre' => 'Recepción de equipo',
            'icono' => 'fas fa-clipboard-check',
            'orden' => 3,
            'controlador' => RecepcionEquipoController::class,
        ],
        [
            'clave' => 'FO-IT-04',
            'nombre' => 'Inventario de herramienta',
            'icono' => 'fas fa-tools',
            'orden' => 4,
            'controlador' => InventarioHerramientaController::class,
        ],
        ['clave' => 'FO-IT-05', 'nombre' => 'Inventario de TI', 'icono' => 'fas fa-laptop', 'orden' => 5],
        ['clave' => 'FO-IT-06', 'nombre' => 'Correos Gmail', 'icono' => 'fas fa-envelope', 'orden' => 6],
        ['clave' => 'FO-IT-07', 'nombre' => 'Correos Alcomex', 'icono' => 'fas fa-inbox', 'orden' => 7],
        ['clave' => 'FO-IT-08', 'nombre' => 'Líneas AT&T', 'icono' => 'fas fa-mobile-screen-button', 'orden' => 8],
        ['clave' => 'FO-IT-09', 'nombre' => 'Inventario de teléfonos activos', 'icono' => 'fas fa-phone', 'orden' => 9],
        ['clave' => 'FO-IT-12', 'nombre' => 'Layout cámaras', 'icono' => 'fas fa-video', 'orden' => 10],
        ['clave' => 'FO-IT-13', 'nombre' => 'Plan anual de mantenimiento a equipo', 'icono' => 'fas fa-calendar-check', 'orden' => 11],
        ['clave' => 'FO-IT-14', 'nombre' => 'Layout cámaras B3', 'icono' => 'fas fa-video', 'orden' => 12],
        ['clave' => 'FO-IT-15', 'nombre' => 'Responsiva celular', 'icono' => 'fas fa-mobile-alt', 'orden' => 13],
        ['clave' => 'FO-IT-16', 'nombre' => 'Carpetas compartidas', 'icono' => 'fas fa-folder-open', 'orden' => 14],
        ['clave' => 'FO-IT-17', 'nombre' => 'Inventario de acceso', 'icono' => 'fas fa-key', 'orden' => 15],
        ['clave' => 'FO-IT-18', 'nombre' => 'Reporte de mantenimiento a equipo', 'icono' => 'fas fa-wrench', 'orden' => 16],
        ['clave' => 'FO-IT-19', 'nombre' => 'Reporte de instalación', 'icono' => 'fas fa-network-wired', 'orden' => 17],
        ['clave' => 'FO-IT-21', 'nombre' => 'Inventario GPS', 'icono' => 'fas fa-satellite-dish', 'orden' => 18],
    ],

];
