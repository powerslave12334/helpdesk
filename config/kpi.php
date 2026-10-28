<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Áreas habilitadas para descargar el reporte KPI Tickets (FO-SGC-08)
    |--------------------------------------------------------------------------
    |
    | El acceso se decide por el ÁREA del usuario, no por su rol: ni Root ni
    | Administrador obtienen acceso por sí solos. Se compara contra
    | tblArea.nombre, así que renombrar un área en Configuración desactiva
    | el acceso de quien esté en ella.
    |
    | Se puede sobrescribir en .env con una lista separada por comas:
    |   KPI_AREAS_PERMITIDAS=Soporte TI,Desarrollo de Software
    |
    */

    'areas_permitidas' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('KPI_AREAS_PERMITIDAS', 'Soporte TI'))
    ))),

];
