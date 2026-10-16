<?php

/*
|--------------------------------------------------------------------------
| Perfiles de tipo de equipo (Inventario > Equipo)
|--------------------------------------------------------------------------
|
| Listas que gobiernan qué campos muestra el formulario de equipo según
| el id_TipoEquipo. Son la fuente única para las vistas (atributos
| data-tipos / data-sin-tipos y render inicial) y para la validación
| del controlador. Los IDs corresponden a tblTipoEquipo.
|
*/

return [

    'ids' => [
        'pc' => 1,
        'monitor' => 2,
        'teclado' => 3,
        'mouse' => 4,
        'regulador' => 5,
        'laptop' => 6,
        'impresora' => 7,
        'camara' => 8,
        'gps' => 9,
    ],

    // Tipos donde la Marca se captura desde dropdown (catálogo).
    // Vacío: TODOS los tipos capturan Marca en texto libre.
    'con_marca_dropdown' => [],

    // Tipos que capturan Ubicación desde dropdown (Departamentos) y
    // ocultan los textos libres de PC (procesador, almacenamiento, SO).
    'con_dropdown' => [1, 2, 3, 4, 5, 6, 7, 8],

    // Tipos donde la Modelo se captura desde dropdown (los demás: texto libre).
    // Vacío: TODOS los tipos capturan Modelo en texto libre
    // (excepto sin_modelo, donde el campo no existe).
    'con_modelo_dropdown' => [],

    // Tipos sin campo Modelo (se guarda cadena vacía; la columna es NOT NULL).
    'sin_modelo' => [3, 4],

    // Tipos que ocultan los campos en común sin contraparte de catálogo:
    // período/último mantenimiento, serie motherboard, memoria RAM, ip y mac.
    // (Los textos libres con dropdown —marca, ubicación, procesador,
    // almacenamiento, SO y modelo— usan con_dropdown / con_modelo_dropdown
    // ∪ sin_modelo como data-sin-tipos en las vistas.)
    'sin_textos' => [2, 3, 4, 5],

    // Campos exclusivos por tipo.
    'campos' => [
        'pc' => [1, 6],
        'monitor' => [2],
        'tipo_teclado' => [3],
        'conectividad' => [3, 4],
        'regulador' => [5],
        // Tipos que muestran Fecha de Adquisición y Costo
        // (periféricos + Impresora, Cámara y GPS).
        'perifericos' => [2, 3, 4, 5, 6, 7, 8, 9],
        // Campos exclusivos de Impresora (tipo de impresora, color,
        // multifunción y funciones).
        'impresora' => [7],
        // Campos exclusivos de Cámara (tipo de cámara, resolución, conexión).
        'camara' => [8],
        // Campos exclusivos de GPS (tipo de GPS, IMEI, operador y línea SIM).
        'gps' => [9],
    ],

    // Tipos donde se ocultan campos internos de PC que no aplican
    // (Serie Motherboard y Memoria RAM). IP, MAC y garantía sí se muestran.
    'sin_internos' => [7, 8, 9],

    // Tipos que ocultan los campos de PC en texto libre
    // (Procesador, Almacenamiento, Sistema Operativo, IP y MAC).
    'sin_textos_pc' => [9],

    // Operadores telefónicos para GPS (lista fija).
    'operadores_gps' => ['Telcel', 'AT&T', 'Movistar', 'Unefon', 'Otro'],
];
