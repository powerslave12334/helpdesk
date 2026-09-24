<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EquipoCampoSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now()->toDateTimeString();

        // Obtener campos existentes por nombre
        $existentes = DB::table('tblEquipoCampo')->pluck('id_EquipoCampo', 'nombre')->toArray();

        // Campos nuevos a insertar (solo los que no existen)
        $camposNuevos = [
            ['nombre' => 'Procesador', 'descripcion' => 'Modelo del procesador', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Memoria RAM', 'descripcion' => 'Cantidad y tipo de memoria RAM', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Tipo Almacenamiento', 'descripcion' => 'Tipo de disco: HDD, SSD, NVMe', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Capacidad Almacenamiento', 'descripcion' => 'Capacidad del disco duro', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Sistema Operativo', 'descripcion' => 'Sistema operativo instalado', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Estado Garantía', 'descripcion' => 'Vigente, Vencida, Sin garantía', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Fecha Fin Garantía', 'descripcion' => 'Fecha de vencimiento de garantía', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Tamaño Pantalla', 'descripcion' => 'Tamaño de pantalla en pulgadas', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Tipo Panel', 'descripcion' => 'Tipo de panel: IPS, VA, TN, OLED', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Resolución', 'descripcion' => 'Resolución de pantalla', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Puertos Disponibles', 'descripcion' => 'HDMI, DisplayPort, VGA, etc.', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Tipo Conexión', 'descripcion' => 'USB, Inalámbrico, PS/2, Bluetooth', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Tipo Teclado', 'descripcion' => 'Mecánico, Membrana, Scissor', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Retroiluminado', 'descripcion' => 'Si/No', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Capacidad VA', 'descripcion' => 'Capacidad en Volt-Amperios', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Número Puertos Regulados', 'descripcion' => 'Cantidad de puertos regulados', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Estado Batería', 'descripcion' => 'Buena, Requiere cambio, Sin batería', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Tiene Cargador', 'descripcion' => 'Si/No', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Tipo Impresora', 'descripcion' => 'Laser, Inkjet, Matricial, MFT', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Tecnología', 'descripcion' => 'Color, Monocromo', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Conectividad', 'descripcion' => 'USB, Red, WiFi, USB+Red', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Tipo Consumible', 'descripcion' => 'Modelo de toner o cartucho', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Conteo Páginas', 'descripcion' => 'Total de páginas impresas', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Tiene Escáner', 'descripcion' => 'Si/No', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Tiene Doble Cara', 'descripcion' => 'Si/No', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Tipo Cámara', 'descripcion' => 'IP, Análoga, WiFi, Bullet, Dome', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Tipo Montaje', 'descripcion' => 'Pared, Techo, Mesa, Poste', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Alcance Nocturno', 'descripcion' => 'Alcance en metros con IR', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Firmware Version', 'descripcion' => 'Versión del firmware', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Tipo GPS', 'descripcion' => 'Vehicular, Personal, Activos', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Protocolo', 'descripcion' => 'LTE-M, NB-IoT, Satelital, 4G, SIP, H.323', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Número IMEI GPS', 'descripcion' => 'IMEI del dispositivo GPS', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Número SIM Asociado', 'descripcion' => 'SIM instalada en el GPS', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Autonomía Batería', 'descripcion' => 'Duración estimada de batería', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Certificación IP', 'descripcion' => 'IP65, IP67, IP68, Sin certificación', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Frecuencia Reporte', 'descripcion' => 'Cada cuánto reporta posición', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Versión SO', 'descripcion' => 'Versión del sistema operativo móvil', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'IMSI', 'descripcion' => 'International Mobile Subscriber Identity', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Tipo SIM', 'descripcion' => 'Física, eSIM', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Tamaño SIM', 'descripcion' => 'Standard, Micro, Nano', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Operador', 'descripcion' => 'Telcel, AT&T, Movistar, etc.', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Plan Asociado', 'descripcion' => 'Descripción del plan contratado', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Estado SIM', 'descripcion' => 'Activa, Suspendida, Cancelada, Nueva', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Fecha Activación', 'descripcion' => 'Fecha de activación de la SIM', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Descripción del Equipo', 'descripcion' => 'Descripción del dispositivo genérico', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Tipo Dispositivo', 'descripcion' => 'Proyector, Escáner, Tablet, Webcam, etc.', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Especificaciones Relevantes', 'descripcion' => 'Características técnicas del equipo', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Tipo UPS', 'descripcion' => 'Line-interactive, Online, Off-line', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Autonomía Batería Min', 'descripcion' => 'Minutos de autonomía con batería', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Fecha Cambio Batería', 'descripcion' => 'Última fecha de cambio de batería', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Tiene Software Monitoreo', 'descripcion' => 'Si/No', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Puerto Comunicación', 'descripcion' => 'USB, RS232, Ethernet, Ninguno', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Potencia Watts', 'descripcion' => 'Potencia en watts', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Tipo Bocina', 'descripcion' => 'Par, Subwoofer, Portátil, Soundbar', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Tiene Micrófono', 'descripcion' => 'Si/No', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Número Puertos', 'descripcion' => 'Cantidad de puertos de red', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Velocidad Puertos', 'descripcion' => '10/100 Mbps, 1 Gbps, 10 Gbps', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Tipo Switch', 'descripcion' => 'Managed, Unmanaged, Smart', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Tiene PoE', 'descripcion' => 'Si/No', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Extensión Telefónica', 'descripcion' => 'Número de extensión telefónica', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Tipo Teléfono', 'descripcion' => 'VoIP, Analógico, IP, Digital', 'fechaAlta' => $now, 'activo' => true],
            ['nombre' => 'Líneas Disponibles', 'descripcion' => 'Número de líneas disponibles', 'fechaAlta' => $now, 'activo' => true],
        ];

        // Solo insertar campos que no existen
        $aInsertar = [];
        foreach ($camposNuevos as $campo) {
            if (!array_key_exists($campo['nombre'], $existentes)) {
                $aInsertar[] = $campo;
            }
        }

        if (!empty($aInsertar)) {
            DB::table('tblEquipoCampo')->insert($aInsertar);
        }

        // Refrescar mapa de campos
        $camposMap = DB::table('tblEquipoCampo')->pluck('id_EquipoCampo', 'nombre')->toArray();

        // Obtener asociaciones existentes
        $asociacionesExistentes = DB::table('tblTipoEquipoCampo')
            ->select('id_TipoEquipo', 'id_EquipoCampo')
            ->get()
            ->map(fn($a) => $a->id_TipoEquipo . '-' . $a->id_EquipoCampo)
            ->toArray();
        $existeAsociacion = fn($tipo, $campo) => in_array($tipo . '-' . $campo, $asociacionesExistentes);

        // ============================================
        // ASOCIACIONES TIPO-CAMPO
        // ============================================
        $asociaciones = [];

        // Helper para agregar asociación solo si no existe
        $agregar = function ($tipo, $campo, $requerido) use ($camposMap, $existeAsociacion, &$asociaciones) {
            if (isset($camposMap[$campo]) && !$existeAsociacion($tipo, $camposMap[$campo])) {
                $asociaciones[] = [
                    'id_TipoEquipo' => $tipo,
                    'id_EquipoCampo' => $camposMap[$campo],
                    'requerido' => $requerido,
                ];
            }
        };

        // PC Escritorio (1)
        foreach (['Serie Motherboard', 'Procesador', 'Memoria RAM'] as $c) $agregar(1, $c, true);
        foreach (['Tipo Almacenamiento', 'Capacidad Almacenamiento', 'Sistema Operativo', 'MAC', 'IP', 'Estado Garantía', 'Fecha Fin Garantía'] as $c) $agregar(1, $c, false);

        // Monitor (2)
        foreach (['Tamaño Pantalla'] as $c) $agregar(2, $c, true);
        foreach (['Puertos Disponibles', 'Estado Garantía', 'Fecha Fin Garantía'] as $c) $agregar(2, $c, false);

        // Teclado (3)
        foreach (['Tipo Conexión'] as $c) $agregar(3, $c, true);
        foreach (['Tipo Teclado', 'Retroiluminado'] as $c) $agregar(3, $c, false);

        // Mouse (4)
        foreach (['Tipo Conexión'] as $c) $agregar(4, $c, true);

        // Regulador (5)
        foreach (['Capacidad VA'] as $c) $agregar(5, $c, true);
        foreach (['Número Puertos Regulados'] as $c) $agregar(5, $c, false);

        // Laptop (6)
        foreach (['Procesador', 'Memoria RAM'] as $c) $agregar(6, $c, true);
        foreach (['Tipo Almacenamiento', 'Capacidad Almacenamiento', 'Tamaño Pantalla', 'Sistema Operativo', 'Estado Batería', 'Tiene Cargador', 'MAC', 'IP', 'Estado Garantía', 'Fecha Fin Garantía'] as $c) $agregar(6, $c, false);

        // Impresora (7)
        foreach (['Tipo Impresora'] as $c) $agregar(7, $c, true);
        foreach (['Tipo Consumible', 'Tiene Escáner', 'Tiene Doble Cara', 'IP', 'MAC', 'Estado Garantía', 'Fecha Fin Garantía'] as $c) $agregar(7, $c, false);

        // Cámaras (8)
        foreach (['Tipo Cámara'] as $c) $agregar(8, $c, true);
        foreach (['Resolución', 'Tipo Montaje', 'Alcance Nocturno', 'Conectividad', 'IP', 'MAC', 'Firmware Version', 'Estado Garantía', 'Fecha Fin Garantía'] as $c) $agregar(8, $c, false);

        // GPS (9)
        foreach (['Tipo GPS'] as $c) $agregar(9, $c, true);
        foreach (['Protocolo', 'Número IMEI GPS', 'Número SIM Asociado', 'Autonomía Batería', 'Certificación IP', 'Frecuencia Reporte', 'Estado Garantía', 'Fecha Fin Garantía'] as $c) $agregar(9, $c, false);

        // Celular (10)
        foreach (['IMEI', 'Sistema Operativo'] as $c) $agregar(10, $c, true);
        foreach (['Versión SO', 'Capacidad Almacenamiento', 'Memoria RAM', 'Estado Batería', 'Estado Garantía', 'Fecha Fin Garantía'] as $c) $agregar(10, $c, false);

        // SIM (11)
        foreach (['Numero SIM', 'Registro sim'] as $c) $agregar(11, $c, true);
        foreach (['IMSI', 'Tipo SIM', 'Tamaño SIM', 'Operador', 'Plan Asociado', 'Estado SIM', 'Fecha Activación'] as $c) $agregar(11, $c, false);

        // Otro (12)
        foreach (['Descripción del Equipo'] as $c) $agregar(12, $c, true);
        foreach (['Tipo Dispositivo', 'Especificaciones Relevantes', 'Estado Garantía', 'Fecha Fin Garantía'] as $c) $agregar(12, $c, false);

        // UPS (13)
        foreach (['Capacidad VA'] as $c) $agregar(13, $c, true);
        foreach (['Tipo UPS', 'Autonomía Batería Min', 'Estado Batería', 'Fecha Cambio Batería', 'Tiene Software Monitoreo', 'Puerto Comunicación', 'Estado Garantía', 'Fecha Fin Garantía'] as $c) $agregar(13, $c, false);

        // Bocina (14)
        foreach (['Tipo Conexión'] as $c) $agregar(14, $c, true);
        foreach (['Potencia Watts', 'Tipo Bocina', 'Tiene Micrófono'] as $c) $agregar(14, $c, false);

        // Switch (15)
        foreach (['Número Puertos', 'Velocidad Puertos'] as $c) $agregar(15, $c, true);
        foreach (['Tipo Switch', 'Tiene PoE', 'IP', 'MAC', 'Firmware Version', 'Estado Garantía', 'Fecha Fin Garantía'] as $c) $agregar(15, $c, false);

        // All in One (16)
        foreach (['Serie Motherboard', 'Procesador', 'Memoria RAM'] as $c) $agregar(16, $c, true);
        foreach (['Tipo Almacenamiento', 'Capacidad Almacenamiento', 'Tamaño Pantalla', 'Resolución', 'Sistema Operativo', 'MAC', 'IP', 'Estado Garantía', 'Fecha Fin Garantía'] as $c) $agregar(16, $c, false);

        // Teléfono Fijo (17)
        foreach (['Extensión Telefónica'] as $c) $agregar(17, $c, true);
        foreach (['Tipo Teléfono', 'Líneas Disponibles', 'MAC', 'IP', 'Protocolo', 'Estado Garantía', 'Fecha Fin Garantía'] as $c) $agregar(17, $c, false);

        // Insertar asociaciones nuevas
        if (!empty($asociaciones)) {
            foreach ($asociaciones as &$a) {
                $a['fechaAlta'] = $now;
                $a['activo'] = true;
            }
            DB::table('tblTipoEquipoCampo')->insert($asociaciones);
        }
    }
}
