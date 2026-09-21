<?php
/**
 * Script de Migración: SQL Server → MySQL Local
 * Ejecutar: php database/migrate_data.php
 */

$sourceConfig = [
    'host' => '192.168.11.20',
    'port' => '1433',
    'database' => 'HELPDESK',
    'username' => 'sa',
    'password' => 'System$2026*',
];

$destConfig = [
    'host' => '127.0.0.1',
    'port' => '3306',
    'database' => 'helpdesk',
    'username' => 'root',
    'password' => '',
];

// Tablas en orden de dependencias (sin foreign keys primero)
$tables = [
    'tblDepto',
    'tblArea',
    'tblTipoEmpleado',
    'tblOficinas',
    'tblModulos',
    'tblAcciones',
    'tblTipoEquipo',
    'tblTipoDesarrollo',
    'tblSistemas',
    'tblSubModulos',
    'tblEstatus',
    'tblTipoSoporte',
    'tblTipoEquipoCampo',
    'tblRoles',
    'tblUsuario',
    'tblLogin',
    'tblRolesUsuarios',
    'tblRolPermiso',
    'tblPermisoUsuarios',
    'tblEquipo',
    'tblEquipoDetalle',
    'tblEquipoUsuario',
    'tblHistorialEquipoUsuario',
    'tblTicket',
    'tblTicketComentarios',
    'tblTicketEvidencia',
    'tblTicketArchivo',
    'tblTicketDesarrolloDetalle',
    'tblNotificaciones',
    'tblConfiguracionSistema',
];

echo "=== Migración SQL Server → MySQL ===\n\n";

// Conectar a SQL Server
echo "Conectando a SQL Server ({$sourceConfig['host']})...\n";
$sourceConn = new PDO(
    "sqlsrv:Server={$sourceConfig['host']},{$sourceConfig['port']};Database={$sourceConfig['database']};TrustServerCertificate=yes",
    $sourceConfig['username'],
    $sourceConfig['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
echo "  ✓ Conectado a SQL Server\n\n";

// Conectar a MySQL
echo "Conectando a MySQL ({$destConfig['host']})...\n";
$destConn = new PDO(
    "mysql:host={$destConfig['host']};port={$destConfig['port']};dbname={$destConfig['database']};charset=utf8mb4",
    $destConfig['username'],
    $destConfig['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
echo "  ✓ Conectado a MySQL\n\n";

// Deshabilitar foreign key checks temporalmente
$destConn->exec("SET FOREIGN_KEY_CHECKS = 0");
$destConn->exec("SET UNIQUE_CHECKS = 0");

$totalRecords = 0;
$startTime = microtime(true);

foreach ($tables as $table) {
    echo "Migrando: $table ... ";

    try {
        // Contar registros en origen
        $countStmt = $sourceConn->query("SELECT COUNT(*) as total FROM [$table]");
        $countRow = $countStmt->fetch(PDO::FETCH_ASSOC);
        $total = (int) $countRow['total'];

        if ($total === 0) {
            echo "(0 registros, omitida)\n";
            continue;
        }

        // Leer todos los registros del origen
        $stmt = $sourceConn->query("SELECT * FROM [$table]");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            echo "(0 registros, omitida)\n";
            continue;
        }

        // Obtener columnas
        $columns = array_keys($rows[0]);
        $columnList = implode(', ', array_map(fn($c) => "`$c`", $columns));
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));

        // Preparar INSERT
        $insertSQL = "INSERT INTO `$table` ($columnList) VALUES ($placeholders)";
        $insertStmt = $destConn->prepare($insertSQL);

        // Limpiar tabla destino
        $destConn->exec("DELETE FROM `$table`");

        // Insertar registros
        $inserted = 0;
        foreach ($rows as $row) {
            $values = [];
            foreach ($row as $key => $value) {
                // Convertir bit a int
                if (is_string($value) && ($value === '0' || $value === '1') && str_contains($key, 'activo') || str_contains($key, 'permitido') || str_contains($key, 'esTecnico') || str_contains($key, 'esCompartido') || str_contains($key, 'enabled') || str_contains($key, 'Leido') || str_contains($key, 'aprobado') || str_contains($key, 'requerido')) {
                    $value = (int) $value;
                }
                $values[] = $value;
            }
            $insertStmt->execute($values);
            $inserted++;
        }

        $totalRecords += $inserted;
        echo "($inserted registros)\n";

    } catch (PDOException $e) {
        echo "ERROR: {$e->getMessage()}\n";
    }
}

// Rehabilitar foreign key checks
$destConn->exec("SET FOREIGN_KEY_CHECKS = 1");
$destConn->exec("SET UNIQUE_CHECKS = 1");

$elapsed = round(microtime(true) - $startTime, 2);

echo "\n=== Resumen ===\n";
echo "Total de registros migrados: $totalRecords\n";
echo "Tiempo total: {$elapsed}s\n";
echo "Tablas procesadas: " . count($tables) . "\n";
echo "\n✓ Migración completada exitosamente\n";
