<?php

namespace App\Support;

/**
 * Controla la conexión de BD activa (local MySQL vs productivo SQL Server).
 * La selección se persiste en un archivo JSON bajo storage/app para que el
 * conmutador funcione sin depender de la base de datos que esté activa.
 */
class ConexionActiva
{
    public const DEFAULT = 'mysql';

    public const OPCIONES = [
        'mysql' => [
            'etiqueta' => 'Local (MySQL)',
            'descripcion' => 'Base de datos local de desarrollo',
        ],
        'sqlsrv_productivo' => [
            'etiqueta' => 'Productivo (SQL Server)',
            'descripcion' => 'Servidor 192.168.11.20 - HELPDESK',
        ],
    ];

    public static function ruta(): string
    {
        return storage_path('app/conexion-activa.json');
    }

    public static function actual(): string
    {
        try {
            $ruta = self::ruta();
            if (!is_file($ruta)) {
                return self::DEFAULT;
            }
            $datos = json_decode((string) file_get_contents($ruta), true);
            $conexion = $datos['conexion'] ?? self::DEFAULT;
        } catch (\Throwable) {
            return self::DEFAULT;
        }

        return array_key_exists($conexion, self::OPCIONES) ? $conexion : self::DEFAULT;
    }

    public static function esProductivo(): bool
    {
        return self::actual() !== self::DEFAULT;
    }

    public static function guardar(string $conexion): void
    {
        if (!array_key_exists($conexion, self::OPCIONES)) {
            throw new \InvalidArgumentException("Conexion no valida: {$conexion}");
        }

        $dir = dirname(self::ruta());
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents(
            self::ruta(),
            json_encode(['conexion' => $conexion], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    public static function etiqueta(string $conexion): string
    {
        return self::OPCIONES[$conexion]['etiqueta'] ?? $conexion;
    }

    public static function info(?string $conexion = null): array
    {
        $conexion = $conexion ?? self::actual();

        return [
            'conexion' => $conexion,
            'etiqueta' => self::etiqueta($conexion),
            'host' => config("database.connections.$conexion.host"),
            'database' => config("database.connections.$conexion.database"),
            'driver' => config("database.connections.$conexion.driver"),
        ];
    }
}
