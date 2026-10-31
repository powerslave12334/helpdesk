<?php
/**
 * Script de Migración: Mantenimientos — MySQL Local → SQL Server Producción
 * Ejecutar: php database/migrate_mantenimientos_a_prod.php
 *
 * Fases:
 *   1. Estatus faltante en prod (id=12 "Pendiente").
 *   2. Catálogo tblPeriodoMantenimiento (5 períodos).
 *   3. Registros de tblMantenimiento (127), conservando los IDs locales.
 *
 * Notas:
 *   - prod tiene IDENTITY en id_Mantenimiento e id_PeriodoMantenimiento,
 *     por eso se usa SET IDENTITY_INSERT ON/OFF para preservar los IDs.
 *   - prod NO tiene foreign keys, así que se inserta todo tal cual.
 */

$sourceConfig = [ // MySQL local (origen)
    'host' => '127.0.0.1',
    'port' => '3306',
    'database' => 'helpdesk',
    'username' => 'root',
    'password' => '',
];

$destConfig = [ // SQL Server productivo (destino)
    'host' => '192.168.11.20',
    'port' => '1433',
    'database' => 'HELPDESK',
    'username' => 'sa',
    'password' => 'System$2026*',
];

echo "=== Migración de Mantenimientos: MySQL Local → SQL Server Prod ===\n\n";

// ── Conexiones ──────────────────────────────────────────────────────────────
echo "Conectando a MySQL local ({$sourceConfig['host']})...\n";
$src = new PDO(
    "mysql:host={$sourceConfig['host']};port={$sourceConfig['port']};dbname={$sourceConfig['database']};charset=utf8mb4",
    $sourceConfig['username'],
    $sourceConfig['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
echo "  ✓ Conectado\n";

echo "Conectando a SQL Server prod ({$destConfig['host']})...\n";
$dst = new PDO(
    "sqlsrv:Server={$destConfig['host']},{$destConfig['port']};Database={$destConfig['database']}",
    $destConfig['username'],
    $destConfig['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
echo "  ✓ Conectado\n\n";

// ── Columnas de tblMantenimiento (las 31, mapeadas explícitamente) ──────────
$columnas = [
    'id_Mantenimiento', 'id_Equipo', 'id_UsuarioTecnico', 'fechaProgramada',
    'fechaEjecucion', 'id_Estatus', 'tipoMantenimiento', 'observaciones',
    'id_Ticket', 'fechaAlta', 'activo', 'estadoInicial',
    'hw_encendido', 'hw_pantalla', 'hw_ventilacion', 'hw_dispositivos',
    'sw_so', 'sw_seguridad', 'sw_antivirus', 'sw_rendimiento',
    'act_limpieza', 'act_revision_hw', 'act_actualizacion_so', 'act_instalacion_sw',
    'act_parches', 'act_eliminacion', 'act_diagnostico', 'act_otro',
    'act_otro_detalle', 'conclusion', 'reporteCerrado',
];

// Columnas que en MySQL son tinyint(1) y en SQL Server son bit → convertir a int.
$bitColumns = [
    'activo', 'reporteCerrado',
    'act_limpieza', 'act_revision_hw', 'act_actualizacion_so', 'act_instalacion_sw',
    'act_parches', 'act_eliminacion', 'act_diagnostico', 'act_otro',
];

$startTime = microtime(true);

try {
    // Comienza la transacción en el destino.
    $dst->beginTransaction();

    // ── Fase 1: Estatus faltante (id=12 "Pendiente", módulo Inventario=2) ───
    echo "Fase 1: Verificando estatus id=12 en prod...\n";
    $existe = $dst->query("SELECT COUNT(*) FROM tblEstatus WHERE id_Estatus = 12")->fetchColumn();
    if ((int) $existe === 0) {
        // id_Estatus también es IDENTITY en prod: requiere IDENTITY_INSERT para el ID explícito.
        $dst->exec("SET IDENTITY_INSERT tblEstatus ON");
        $dst->exec("INSERT INTO tblEstatus (id_Estatus, descripcion, id_Modulos, activo) VALUES (12, 'Pendiente', 2, 1)");
        $dst->exec("SET IDENTITY_INSERT tblEstatus OFF");
        echo "  ✓ Insertado estatus 12 'Pendiente'\n";
    } else {
        echo "  • Ya existe, se omite\n";
    }

    // ── Fase 2: Catálogo de períodos (5 registros) ──────────────────────────
    echo "Fase 2: Migrando tblPeriodoMantenimiento...\n";
    $periodos = $src->query("SELECT id_PeriodoMantenimiento, descripcion, meses, activo FROM tblperiodomantenimiento ORDER BY id_PeriodoMantenimiento")->fetchAll(PDO::FETCH_ASSOC);
    if (count($periodos) > 0) {
        $dst->exec("SET IDENTITY_INSERT tblPeriodoMantenimiento ON");
        $sql = "INSERT INTO tblPeriodoMantenimiento (id_PeriodoMantenimiento, descripcion, meses, activo) VALUES (?, ?, ?, ?)";
        $ins = $dst->prepare($sql);
        $insertados = 0;
        foreach ($periodos as $p) {
            $ins->execute([
                (int) $p['id_PeriodoMantenimiento'],
                $p['descripcion'],
                (int) $p['meses'],
                (int) $p['activo'],
            ]);
            $insertados++;
        }
        $dst->exec("SET IDENTITY_INSERT tblPeriodoMantenimiento OFF");
        echo "  ✓ {$insertados} períodos insertados\n";
    } else {
        echo "  • Sin períodos en local, se omite\n";
    }

    // ── Fase 3: Registros de mantenimiento ──────────────────────────────────
    echo "Fase 3: Migrando tblMantenimiento...\n";
    $rows = $src->query("SELECT " . implode(', ', array_map(fn($c) => "`$c`", $columnas)) . " FROM tblmantenimiento ORDER BY id_Mantenimiento")->fetchAll(PDO::FETCH_ASSOC);

    $dst->exec("SET IDENTITY_INSERT tblMantenimiento ON");
    $colList = implode(', ', $columnas);
    $ph = implode(', ', array_fill(0, count($columnas), '?'));
    $ins = $dst->prepare("INSERT INTO tblMantenimiento ($colList) VALUES ($ph)");

    $insertados = 0;
    foreach ($rows as $row) {
        $vals = [];
        foreach ($columnas as $c) {
            $v = $row[$c];
            if (in_array($c, $bitColumns, true)) {
                $v = ($v === null) ? null : (int) $v; // tinyint(1) → bit
            }
            $vals[] = $v;
        }
        $ins->execute($vals);
        $insertados++;
    }
    $dst->exec("SET IDENTITY_INSERT tblMantenimiento OFF");
    echo "  ✓ {$insertados} mantenimientos insertados\n";

    // Confirma la transacción.
    $dst->commit();
    echo "\n✓ Transacción confirmada (commit)\n";

} catch (PDOException $e) {
    // Revierte todo si algo falla: no queda nada a medias en prod.
    if ($dst->inTransaction()) {
        $dst->rollBack();
        echo "\n✗ ERROR, se revirtió la transacción (rollback): {$e->getMessage()}\n";
    } else {
        echo "\n✗ ERROR: {$e->getMessage()}\n";
    }
    exit(1);
}

// ── Verificación posterior ──────────────────────────────────────────────────
echo "\n=== Verificación ===\n";
$total = $dst->query("SELECT COUNT(*) FROM tblMantenimiento")->fetchColumn();
echo "tblMantenimiento en prod: {$total} registros\n";

$per = $dst->query("SELECT COUNT(*) FROM tblPeriodoMantenimiento")->fetchColumn();
echo "tblPeriodoMantenimiento en prod: {$per} registros\n";

// Ningún mantenimiento debe quedar con un id_Estatus que no exista en tblEstatus.
$huerfanos = $dst->query("SELECT COUNT(*) FROM tblMantenimiento m LEFT JOIN tblEstatus e ON e.id_Estatus = m.id_Estatus WHERE e.id_Estatus IS NULL")->fetchColumn();
echo "Mantenimientos con estatus huérfano: {$huerfanos}\n";

// Sanity check de integridad de IDs referenciados.
$fk = $dst->query("SELECT COUNT(*) FROM tblMantenimiento m
    LEFT JOIN tblEquipo eq ON eq.id_Equipo = m.id_Equipo
    LEFT JOIN tblUsuario u ON u.id_Usuario = m.id_UsuarioTecnico
    WHERE eq.id_Equipo IS NULL OR u.id_Usuario IS NULL")->fetchColumn();
echo "Mantenimientos con equipo/técnico inexistente: {$fk}\n";

$elapsed = round(microtime(true) - $startTime, 2);
echo "\nTiempo total: {$elapsed}s\n";
echo "✓ Migración de mantenimientos completada\n";
