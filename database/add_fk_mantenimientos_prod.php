<?php
/**
 * Script: Agregar llaves foráneas de tblMantenimiento en SQL Server Producción
 * Ejecutar: php database/add_fk_mantenimientos_prod.php
 *
 * Crea las 4 FK que tiene el esquema local y faltan en producción:
 *   - tblMantenimiento.id_Equipo         -> tblEquipo.id_Equipo
 *   - tblMantenimiento.id_UsuarioTecnico -> tblUsuario.id_Usuario
 *   - tblMantenimiento.id_Estatus        -> tblEstatus.id_Estatus
 *   - tblMantenimiento.id_Ticket         -> tblTicket.id_Ticket
 *
 * Notas:
 *   - Idempotente: verifica antes de crear cada FK.
 *   - Valida datos huérfanos antes de crear; aborta si los hay.
 *   - Crea el índice por columna si SQL Server no lo genera solo.
 */

$destConfig = [
    'host' => '192.168.11.20',
    'port' => '1433',
    'database' => 'HELPDESK',
    'username' => 'sa',
    'password' => 'System$2026*',
];

// [columna hija, tabla padre, columna padre, nombre FK local, nombre índice local]
$fks = [
    ['id_Equipo',         'tblEquipo', 'id_Equipo',         'tblmantenimiento_id_equipo_foreign',         'tblmantenimiento_id_equipo_foreign'],
    ['id_UsuarioTecnico', 'tblUsuario','id_Usuario',        'tblmantenimiento_id_usuariotecnico_foreign', 'tblmantenimiento_id_usuariotecnico_foreign'],
    ['id_Estatus',        'tblEstatus','id_Estatus',        'tblmantenimiento_id_estatus_foreign',        'tblmantenimiento_id_estatus_foreign'],
    ['id_Ticket',         'tblTicket', 'id_Ticket',         'tblmantenimiento_id_ticket_foreign',         'tblmantenimiento_id_ticket_foreign'],
];

echo "=== Agregar FK de tblMantenimiento en SQL Server Prod ===\n\n";

echo "Conectando a SQL Server prod ({$destConfig['host']})...\n";
$dst = new PDO(
    "sqlsrv:Server={$destConfig['host']},{$destConfig['port']};Database={$destConfig['database']}",
    $destConfig['username'],
    $destConfig['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
echo "  ✓ Conectado\n\n";

// ── Validación previa: tipos y datos huérfanos ──────────────────────────────
echo "Validación previa...\n";

// 1) Tipos idénticos (SQL Server exige igualdad exacta para FK).
$tipos = [];
$sql = "SELECT OBJECT_NAME(c.object_id) AS t, c.name AS col, t2.name AS ty
        FROM sys.columns c JOIN sys.types t2 ON c.user_type_id = t2.user_type_id
        WHERE (OBJECT_NAME(c.object_id)='tblMantenimiento' AND c.name IN ('id_Equipo','id_UsuarioTecnico','id_Estatus','id_Ticket'))
           OR (OBJECT_NAME(c.object_id)='tblEquipo'  AND c.name='id_Equipo')
           OR (OBJECT_NAME(c.object_id)='tblUsuario' AND c.name='id_Usuario')
           OR (OBJECT_NAME(c.object_id)='tblEstatus' AND c.name='id_Estatus')
           OR (OBJECT_NAME(c.object_id)='tblTicket'  AND c.name='id_Ticket')";
foreach ($dst->query($sql) as $r) { $tipos[strtolower($r["t"]) . "." . strtolower($r["col"])] = $r["ty"]; }

$map = [
    ['id_Equipo',         'tblEquipo'],
    ['id_UsuarioTecnico', 'tblUsuario'],
    ['id_Estatus',        'tblEstatus'],
    ['id_Ticket',         'tblTicket'],
];
foreach ($map as [$col, $ref]) {
    $a = $tipos["tblmantenimiento." . strtolower($col)] ?? '?';
    $b = $tipos[strtolower($ref) . "." . strtolower($col === 'id_UsuarioTecnico' ? 'id_Usuario' : $col)] ?? '?';
    $ok = ($a === $b);
    echo "  " . ($ok ? '✓' : '✗') . " tblMantenimiento.$col ($a) -> $ref (" . ($col === 'id_UsuarioTecnico' ? 'id_Usuario' : $col) . ") ($b)" . ($ok ? '' : '  ← TIPO DISTINTO') . "\n";
    if (!$ok) { echo "\n✗ Tipos incompatibles, abortando.\n"; exit(1); }
}

// 2) Datos huérfanos: cada fila debe tener el padre existente (o el FK en NULL).
$huerfanos = [
    'id_Equipo'         => "SELECT COUNT(*) FROM tblMantenimiento m WHERE m.id_Equipo IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tblEquipo  p WHERE p.id_Equipo  = m.id_Equipo)",
    'id_UsuarioTecnico' => "SELECT COUNT(*) FROM tblMantenimiento m WHERE m.id_UsuarioTecnico IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tblUsuario p WHERE p.id_Usuario = m.id_UsuarioTecnico)",
    'id_Estatus'        => "SELECT COUNT(*) FROM tblMantenimiento m WHERE m.id_Estatus IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tblEstatus p WHERE p.id_Estatus = m.id_Estatus)",
    'id_Ticket'         => "SELECT COUNT(*) FROM tblMantenimiento m WHERE m.id_Ticket IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tblTicket  p WHERE p.id_Ticket  = m.id_Ticket)",
];
$hayHuerfanos = false;
foreach ($huerfanos as $col => $q) {
    $n = (int) $dst->query($q)->fetchColumn();
    echo "  " . ($n === 0 ? '✓' : '✗') . " $col huérfanos: $n\n";
    if ($n > 0) { $hayHuerfanos = true; }
}
if ($hayHuerfanos) { echo "\n✗ Hay datos huérfanos, abortando (no se creó nada).\n"; exit(1); }
echo "  ✓ Validación superada\n\n";

// ── Creación de FK + índices ────────────────────────────────────────────────
echo "Creando llaves foráneas...\n";
$startTime = microtime(true);

try {
    $dst->beginTransaction();

    foreach ($fks as [$col, $refTable, $refCol, $fkName, $idxName]) {
        // ¿Ya existe la FK?
        $existe = $dst->query("SELECT COUNT(*) FROM sys.foreign_keys WHERE name = " . $dst->quote($fkName))->fetchColumn();
        if ((int) $existe > 0) {
            echo "  • $fkName ya existe, se omite\n";
        } else {
            $dst->exec(
                "ALTER TABLE tblMantenimiento WITH CHECK ADD CONSTRAINT [$fkName] " .
                "FOREIGN KEY ([$col]) REFERENCES [$refTable] ([$refCol])"
            );
            echo "  ✓ FK creada: $col -> $refTable.$refCol  [$fkName]\n";
        }

        // Asegurar índice en la columna hija (para el rendimiento de la FK).
        // sys.index_columns: ¿existe algún índice (que no sea PK) que incluya $col?
        $st = $dst->prepare(
            "SELECT COUNT(DISTINCT i.index_id) FROM sys.indexes i
             JOIN sys.index_columns ic ON i.object_id = ic.object_id AND i.index_id = ic.index_id
             WHERE i.object_id = OBJECT_ID('tblMantenimiento')
               AND i.is_primary_key = 0 AND i.type > 0
               AND ic.column_id = (SELECT column_id FROM sys.columns WHERE object_id = OBJECT_ID('tblMantenimiento') AND name = ?)"
        );
        $st->execute([$col]);
        $tieneIdx = (int) $st->fetchColumn();

        if ($tieneIdx === 0) {
            $dst->exec("CREATE NONCLUSTERED INDEX [$idxName] ON tblMantenimiento ([$col])");
            echo "    ✓ Índice creado: [$idxName] sobre ($col)\n";
        } else {
            echo "    • Índice ya existe sobre ($col)\n";
        }
    }

    $dst->commit();
    echo "\n✓ Transacción confirmada (commit)\n";

} catch (PDOException $e) {
    if ($dst->inTransaction()) {
        $dst->rollBack();
        echo "\n✗ ERROR, se revirtió (rollback): {$e->getMessage()}\n";
    } else {
        echo "\n✗ ERROR: {$e->getMessage()}\n";
    }
    exit(1);
}

$elapsed = round(microtime(true) - $startTime, 2);

// ── Verificación final ──────────────────────────────────────────────────────
echo "\n=== Verificación ===\n";
echo "FK en tblMantenimiento:\n";
$sql = "SELECT fk.name AS fkname,
        COL_NAME(fkc.parent_object_id, fkc.parent_column_id) AS col,
        OBJECT_NAME(fkc.referenced_object_id) AS reftabla,
        COL_NAME(fkc.referenced_object_id, fkc.referenced_column_id) AS refcol
        FROM sys.foreign_keys fk
        JOIN sys.foreign_key_columns fkc ON fk.object_id = fkc.constraint_object_id
        WHERE OBJECT_NAME(fk.parent_object_id) = 'tblMantenimiento'
        ORDER BY fk.name";
$n = 0;
foreach ($dst->query($sql) as $r) {
    echo "  {$r["col"]} -> {$r["reftabla"]}.{$r["refcol"]}  [{$r["fkname"]}]\n";
    $n++;
}
echo "Total FK en tblMantenimiento: $n (esperado 4)\n";

echo "\nÍndices en tblMantenimiento:\n";
$sql = "SELECT i.name, i.is_primary_key FROM sys.indexes i
        WHERE i.object_id = OBJECT_ID('tblMantenimiento') AND i.type > 0 ORDER BY i.name";
foreach ($dst->query($sql) as $r) {
    echo "  {$r["name"]}" . ($r["is_primary_key"] ? " (PK)" : "") . "\n";
}

$filas = $dst->query("SELECT COUNT(*) FROM tblMantenimiento")->fetchColumn();
echo "\nFilas en tblMantenimiento: $filas (sin cambios)\n";
echo "\nTiempo: {$elapsed}s\n";
echo "✓ Llaves foráneas aplicadas\n";
