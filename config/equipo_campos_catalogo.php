<?php

/*
|--------------------------------------------------------------------------
| Catálogo Maestro de Campos de Equipo (tblEquipo)
|--------------------------------------------------------------------------
|
| Define los campos configurables que se pueden asignar o desasignar
| a cada Tipo de Equipo desde el panel de personalización.
|
*/

return [
    'secciones' => [
        'general' => [
            'titulo' => 'Información General e Identificación',
            'icono' => 'fas fa-info-circle',
            'descripcion' => 'Datos básicos y ubicación del activo.',
            'campos' => [
                'marca' => ['etiqueta' => 'Marca', 'tipo' => 'texto', 'ayuda' => 'Marca del equipo o fabricante'],
                'modelo' => ['etiqueta' => 'Modelo', 'tipo' => 'texto', 'ayuda' => 'Modelo del equipo'],
                'numeroSerie' => ['etiqueta' => 'Número de Serie', 'tipo' => 'texto', 'ayuda' => 'Serie única del fabricante'],
                'nombreEquipo' => ['etiqueta' => 'Nombre del Equipo', 'tipo' => 'texto', 'ayuda' => 'Nombre identificador en red o etiqueta'],
                'ubicacion' => ['etiqueta' => 'Ubicación (Departamento)', 'tipo' => 'texto', 'ayuda' => 'Departamento o área física'],
                'id_Oficinas' => ['etiqueta' => 'Oficina / Sucursal', 'tipo' => 'select', 'ayuda' => 'Sede u oficina'],
                'esCompartido' => ['etiqueta' => 'Equipo Compartido', 'tipo' => 'boolean', 'ayuda' => 'Indica si es de uso común por varios usuarios'],
                'observaciones' => ['etiqueta' => 'Observaciones', 'tipo' => 'textarea', 'ayuda' => 'Notas adicionales'],
            ],
        ],

        'garantia_costos' => [
            'titulo' => 'Adquisición, Costos y Garantía',
            'icono' => 'fas fa-dollar-sign',
            'descripcion' => 'Valores de compra, pólizas y fechas de garantía.',
            'campos' => [
                'fechaAdquisicion' => ['etiqueta' => 'Fecha de Adquisición', 'tipo' => 'date', 'ayuda' => 'Fecha de compra o entrega'],
                'costo' => ['etiqueta' => 'Costo', 'tipo' => 'decimal', 'ayuda' => 'Precio de adquisición'],
                'estadoGarantia' => ['etiqueta' => 'Estado de Garantía', 'tipo' => 'texto', 'ayuda' => 'Vigente, Expirada, etc.'],
                'fechaFinGarantia' => ['etiqueta' => 'Fecha Fin de Garantía', 'tipo' => 'date', 'ayuda' => 'Vencimiento de la garantía'],
            ],
        ],

        'mantenimiento' => [
            'titulo' => 'Mantenimiento y Ciclo de Servicio',
            'icono' => 'fas fa-tools',
            'descripcion' => 'Control de calendario preventivo y último servicio.',
            'campos' => [
                'id_PeriodoMantenimiento' => ['etiqueta' => 'Período de Mantenimiento', 'tipo' => 'select', 'ayuda' => 'Frecuencia (mensual, trimestral, etc.)'],
                'fechaUltimoMantenimiento' => ['etiqueta' => 'Último Mantenimiento', 'tipo' => 'datetime', 'ayuda' => 'Fecha de la última intervención'],
            ],
        ],

        'computo' => [
            'titulo' => 'Cómputo y Hardware Interno',
            'icono' => 'fas fa-microchip',
            'descripcion' => 'Procesador, memorias, almacenamiento y sistema operativo.',
            'campos' => [
                'serieMotherboard' => ['etiqueta' => 'Serie Motherboard', 'tipo' => 'texto', 'ayuda' => 'Número de serie de placa madre'],
                'procesador' => ['etiqueta' => 'Procesador', 'tipo' => 'texto', 'ayuda' => 'Modelo de CPU'],
                'memoriaRam' => ['etiqueta' => 'Memoria RAM', 'tipo' => 'texto', 'ayuda' => 'Capacidad de memoria RAM (ej. 16GB DDR4)'],
                'almacenamiento' => ['etiqueta' => 'Tipo de Almacenamiento', 'tipo' => 'texto', 'ayuda' => 'SSD, NVMe, HDD'],
                'tamanoAlmacenamiento' => ['etiqueta' => 'Capacidad Almacenamiento', 'tipo' => 'texto', 'ayuda' => 'Ej. 512GB, 1TB'],
                'sistemaOperativo' => ['etiqueta' => 'Sistema Operativo', 'tipo' => 'texto', 'ayuda' => 'Windows, Linux, macOS, etc.'],
                'especificaciones' => ['etiqueta' => 'Especificaciones Adicionales', 'tipo' => 'textarea', 'ayuda' => 'Detalles técnicos complementarios'],
            ],
        ],

        'red' => [
            'titulo' => 'Conectividad y Red',
            'icono' => 'fas fa-network-wired',
            'descripcion' => 'Direcciones IP y físicas MAC.',
            'campos' => [
                'ip' => ['etiqueta' => 'Dirección IP', 'tipo' => 'texto', 'ayuda' => 'IPv4 asignada'],
                'mac' => ['etiqueta' => 'Dirección MAC', 'tipo' => 'texto', 'ayuda' => 'Dirección física de tarjeta de red'],
            ],
        ],

        'monitor' => [
            'titulo' => 'Pantallas y Monitores',
            'icono' => 'fas fa-tv',
            'descripcion' => 'Tamaño de pantalla y conectores de video.',
            'campos' => [
                'tamano' => ['etiqueta' => 'Tamaño de Pantalla (Pulgadas)', 'tipo' => 'texto', 'ayuda' => 'Ej. 24", 27"'],
                'puertos' => ['etiqueta' => 'Puertos de Video', 'tipo' => 'tags', 'ayuda' => 'HDMI, DisplayPort, VGA, etc.'],
            ],
        ],

        'perifericos_energia' => [
            'titulo' => 'Periféricos y Energía Eléctrica',
            'icono' => 'fas fa-plug',
            'descripcion' => 'Teclados, mouse, reguladores y sistemas UPS.',
            'campos' => [
                'tipoTeclado' => ['etiqueta' => 'Tipo de Teclado', 'tipo' => 'texto', 'ayuda' => 'Mecánico, Membrana, etc.'],
                'conectividad' => ['etiqueta' => 'Conectividad', 'tipo' => 'texto', 'ayuda' => 'USB, Bluetooth, Inalámbrico 2.4GHz'],
                'capacidad' => ['etiqueta' => 'Capacidad Eléctrica', 'tipo' => 'texto', 'ayuda' => 'Ej. 1000VA, 500W'],
                'voltajeEntrada' => ['etiqueta' => 'Voltaje de Entrada', 'tipo' => 'texto', 'ayuda' => 'Ej. 120V AC'],
                'voltajeSalida' => ['etiqueta' => 'Voltaje de Salida', 'tipo' => 'texto', 'ayuda' => 'Ej. 120V AC'],
                'contactos' => ['etiqueta' => 'Número de Contactos / Tomas', 'tipo' => 'integer', 'ayuda' => 'Tomas de corriente disponibles'],
            ],
        ],

        'impresion' => [
            'titulo' => 'Impresoras y Escáneres',
            'icono' => 'fas fa-print',
            'descripcion' => 'Propiedades de impresión y fotocopiado.',
            'campos' => [
                'tipoImpresora' => ['etiqueta' => 'Tipo de Impresora', 'tipo' => 'texto', 'ayuda' => 'Láser, Tinta continua, Térmica'],
                'colorImpresion' => ['etiqueta' => 'Color de Impresión', 'tipo' => 'select', 'ayuda' => 'Color o Monocromática'],
                'multifuncion' => ['etiqueta' => 'Multifunción', 'tipo' => 'boolean', 'ayuda' => 'Si cuenta con escáner/copiadora'],
                'funciones' => ['etiqueta' => 'Funciones', 'tipo' => 'tags', 'ayuda' => 'Imprimir, Copiar, Escanear, Fax'],
            ],
        ],

        'camara' => [
            'titulo' => 'Cámaras y Videovigilancia',
            'icono' => 'fas fa-video',
            'descripcion' => 'Cámaras IP, CCTV y webcam.',
            'campos' => [
                'tipoCamara' => ['etiqueta' => 'Tipo de Cámara', 'tipo' => 'texto', 'ayuda' => 'Domo, Bala, PTZ, Web'],
                'resolucion' => ['etiqueta' => 'Resolución', 'tipo' => 'texto', 'ayuda' => '1MP, 2MP, 4MP, 4K'],
                'conexion' => ['etiqueta' => 'Tipo de Conexión', 'tipo' => 'texto', 'ayuda' => 'PoE, Cableado, Inalámbrica'],
            ],
        ],

        'gps' => [
            'titulo' => 'Rastreo GPS y Telefonía',
            'icono' => 'fas fa-location-dot',
            'descripcion' => 'Módulos satelitales y líneas celulares asociadas.',
            'campos' => [
                'tipoGps' => ['etiqueta' => 'Tipo de GPS', 'tipo' => 'texto', 'ayuda' => 'Fijo, Portátil, OBD'],
                'imei' => ['etiqueta' => 'IMEI (15 dígitos)', 'tipo' => 'texto', 'ayuda' => 'Identificador internacional de equipo móvil'],
                'operador' => ['etiqueta' => 'Operador Celular', 'tipo' => 'texto', 'ayuda' => 'Telcel, AT&T, Movistar, etc.'],
                'lineaSim' => ['etiqueta' => 'Número de Línea / SIM', 'tipo' => 'texto', 'ayuda' => 'Número de teléfono o ICCID'],
            ],
        ],
    ],
];
