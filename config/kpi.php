<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Áreas que alimentan los reportes KPI (FO-SGC-08)
    |--------------------------------------------------------------------------
    |
    | IMPORTANTE: el ACCESO a la descarga de los tres reportes KPI (Tickets,
    | Desarrollo y GPS) ya no se decide por área, sino por ROL: solo los
    | roles Administrador y Root los ven y pueden descargar
    | (ver PermisosService::puedeDescargarKpi*() y esAdministrador()).
    |
    | Las listas de abajo siguen definiendo qué datos ENTRA en cada reporte,
    | no quién puede descargarlo.
    |
    | Se puede sobrescribir en .env con una lista separada por comas:
    |   KPI_AREAS_DESARROLLO=Desarrollo de Software
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Áreas cuyos tickets alimentan el reporte KPI Desarrollo
    |--------------------------------------------------------------------------
    |
    | Se compara contra tblArea.nombre vía tblTipoSoporte.id_Area, así que
    | renombrar un área en Configuración cambia qué tickets entran al reporte.
    | Un usuario en ambas áreas verá los dos botones (si su rol lo permite).
    |
    |   KPI_AREAS_DESARROLLO=Desarrollo de Software
    |
    */

    'areas_desarrollo' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('KPI_AREAS_DESARROLLO', 'Desarrollo de Software'))
    ))),

];
