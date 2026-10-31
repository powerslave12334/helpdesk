<?php
/**
 * Sincronización PRODUCCIÓN (SQL Server) → LOCAL (MySQL)
 *
 * Inserta en local únicamente los registros que faltan (insert-if-missing).
 * Nunca borra ni sobreescribe registros existentes.
 * Transacción con rollback ante cualquier error. Re-ejecutable.
 *
 * Fase 1: recrea en local las 6 tablas que existen solo en producción
 *         (tblRolPermiso, tblPermisoUsuarios, tblAcciones, tblEquipoCampo,
 *         tblTipoEquipoCampo, tblEquipoDetalle). En local fueron retiradas a
 *         propósito por las migraciones 2026_09_28_000004/000005; se crean de
 *         nuevo SOLO para resguardar sus datos, con el mismo esquema que tiene
 *         producción y SIN foreign keys (producción tampoco las tiene).
 * Fase 2: copia los registros faltantes.
 *
 * Ejecutar: php database/sync_prod_a_local.php
 * Previo:   php database/backup_prod.php
 */

$localConfig = [
    'host' => '127.0.0.1',
    'port' => '3306',
    'database' => 'helpdesk',
    'username' => 'root',
    'password' => '',
];
$prodConfig = [
    'host' => '192.168.11.20',
    'port' => '1433',
    'database' => 'HELPDESK',
    'username' => 'sa',
    'password' => 'System$2026*',
];

echo "=== Sincronización PRODUCCIÓN → LOCAL (insert-if-missing) ===\n";

$local = new PDO(
    "mysql:host={$localConfig['host']};port={$localConfig['port']};dbname={$localConfig['database']};charset=utf8mb4",
    $localConfig['username'],
    $localConfig['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$prod = new PDO(
    "sqlsrv:Server={$prodConfig['host']},{$prodConfig['port']};Database={$prodConfig['database']}",
    $prodConfig['username'],
    $prodConfig['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
echo "  ✓ Producción (SQL Server) y Local (MySQL) conectados\n\n";

// ---------------------------------------------------------------------------
// Fase 1: tablas legacy (solo existen en producción)
// ---------------------------------------------------------------------------
$ddl = [
    'tblAcciones' => "CREATE TABLE IF NOT EXISTS `tblAcciones` (
        `id_Accion` INT NOT NULL AUTO_INCREMENT,
        `nombre` VARCHAR(50) NOT NULL,
        `fechaAlta` DATETIME NOT NULL,
        `activo` TINYINT(1) NOT NULL,
        PRIMARY KEY (`id_Accion`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'tblEquipoCampo' => "CREATE TABLE IF NOT EXISTS `tblEquipoCampo` (
        `id_EquipoCampo` INT NOT NULL AUTO_INCREMENT,
        `nombre` VARCHAR(100) NOT NULL,
        `descripcion` VARCHAR(250) NULL,
        `fechaAlta` DATETIME NOT NULL,
        `activo` TINYINT(1) NOT NULL,
        PRIMARY KEY (`id_EquipoCampo`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'tblTipoEquipoCampo' => "CREATE TABLE IF NOT EXISTS `tblTipoEquipoCampo` (
        `id_TipoEquipoCampo` INT NOT NULL AUTO_INCREMENT,
        `id_TipoEquipo` INT NOT NULL,
        `id_EquipoCampo` INT NOT NULL,
        `requerido` TINYINT(1) NOT NULL,
        `fechaAlta` DATETIME NOT NULL,
        `activo` TINYINT(1) NOT NULL,
        PRIMARY KEY (`id_TipoEquipoCampo`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'tblEquipoDetalle' => "CREATE TABLE IF NOT EXISTS `tblEquipoDetalle` (
        `id_EquipoDetalle` INT NOT NULL AUTO_INCREMENT,
        `id_Equipo` INT NOT NULL,
        `id_EquipoCampo` INT NOT NULL,
        `valor` VARCHAR(250) NOT NULL,
        `fechaAlta` DATETIME NOT NULL,
        `activo` TINYINT(1) NOT NULL,
        PRIMARY KEY (`id_EquipoDetalle`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'tblRolPermiso' => "CREATE TABLE IF NOT EXISTS `tblRolPermiso` (
        `id_RolPermiso` INT NOT NULL AUTO_INCREMENT,
        `id_Rol` INT NOT NULL,
        `id_Modulos` INT NOT NULL,
        `id_SubModulos` INT NOT NULL,
        `id_Accion` INT NOT NULL,
        `permitido` TINYINT(1) NOT NULL,
        `fechaAlta` DATETIME NOT NULL,
        `activo` TINYINT(1) NOT NULL,
        PRIMARY KEY (`id_RolPermiso`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'tblPermisoUsuarios' => "CREATE TABLE IF NOT EXISTS `tblPermisoUsuarios` (
        `id_PermisoUsuarios` INT NOT NULL AUTO_INCREMENT,
        `id_Usuario` INT NOT NULL,
        `id_Modulos` INT NOT NULL,
        `id_SubModulos` INT NOT NULL,
        `id_Accion` INT NOT NULL,
        `permitido` TINYINT(1) NOT NULL,
        `fechaAlta` DATETIME NOT NULL,
        `activo` TINYINT(1) NOT NULL,
        PRIMARY KEY (`id_PermisoUsuarios`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

echo "  -- Fase 1: tablas legacy (solo en producción) --\n";
foreach ($ddl as $tabla => $sql) {
    $local->exec($sql);
    $n = (int) $local->query("SELECT COUNT(*) FROM `{$tabla}`")->fetchColumn();
    echo "  ✓ {$tabla}: creada/verificada ({$n} filas locales)\n";
}

// ---------------------------------------------------------------------------
// FK lógicas (las que define el esquema local) para evitar huérfanos.
// ---------------------------------------------------------------------------
$fkMap = [
    'tblequipousuario' => [
        'id_equipo' => ['tblEquipo', 'id_Equipo'],
        'id_usuario' => ['tblUsuario', 'id_Usuario'],
        'id_area' => ['tblArea', 'id_Area'],
    ],
    'tblnotificaciones' => ['id_usuariodestino' => ['tblUsuario', 'id_Usuario']],
    'tblticket' => [
        'id_equipo' => ['tblEquipo', 'id_Equipo'],
        'id_estatus' => ['tblEstatus', 'id_Estatus'],
        'id_tecnico' => ['tblUsuario', 'id_Usuario'],
        'id_tiposoporte' => ['tblTipoSoporte', 'id_TipoSoporte'],
        'id_usuario' => ['tblUsuario', 'id_Usuario'],
    ],
    'tblticketcomentarios' => [
        'id_ticket' => ['tblTicket', 'id_Ticket'],
        'id_usuario' => ['tblUsuario', 'id_Usuario'],
        'id_estatus' => ['tblEstatus', 'id_Estatus'],
    ],
    'tblrolesusuarios' => [
        'id_rol' => ['tblRoles', 'id_Rol'],
        'id_usuario' => ['tblUsuario', 'id_Usuario'],
    ],
    'tblsubmodulos' => ['id_modulos' => ['tblModulos', 'id_Modulos']],
    'tblrolmodulo' => [
        'id_rol' => ['tblRoles', 'id_Rol'],
        'id_modulos' => ['tblModulos', 'id_Modulos'],
    ],
    'tblrolsubmodulo' => [
        'id_rol' => ['tblRoles', 'id_Rol'],
        'id_submodulos' => ['tblSubModulos', 'id_SubModulos'],
    ],
    // Tablas legacy: sin FK en local (se copian tal cual, como en producción).
    'tblrolpermiso' => [],
    'tblpermisousuarios' => [],
    'tblacciones' => [],
    'tlbequippocampo' => [],
];

// Plan en orden: legacy primero (sin dependencias), luego las de negocio.
$plan = [
    ['origen' => 'tblAcciones',           'dest' => 'tblAcciones',           'natural' => []],
    ['origen' => 'tblEquipoCampo',        'dest' => 'tblEquipoCampo',        'natural' => []],
    ['origen' => 'tblTipoEquipoCampo',    'dest' => 'tblTipoEquipoCampo',    'natural' => []],
    ['origen' => 'tblEquipoDetalle',      'dest' => 'tblEquipoDetalle',      'natural' => []],
    ['origen' => 'tblRolPermiso',         'dest' => 'tblRolPermiso',         'natural' => []],
    ['origen' => 'tblPermisoUsuarios',    'dest' => 'tblPermisoUsuarios',    'natural' => []],
    ['origen' => 'tblEquipoUsuario',      'dest' => 'tblequipousuario',      'natural' => ['id_equipo', 'id_usuario']],
    ['origen' => 'tblNotificaciones',     'dest' => 'tblnotificaciones',     'natural' => []],
    ['origen' => 'tblTicket',             'dest' => 'tblticket',             'natural' => []],
    ['origen' => 'tblTicketComentarios',  'dest' => 'tblticketcomentarios',  'natural' => []],
    ['origen' => 'tblRolesUsuarios',      'dest' => 'tblrolesusuarios',      'natural' => ['id_rol', 'id_usuario']],
    ['origen' => 'tblSubModulos',         'dest' => 'tblsubmodulos',         'natural' => ['id_modulos', 'nombre']],
    ['origen' => 'tblRolModulo',          'dest' => 'tblrolmodulo',          'natural' => ['id_rol', 'id_modulos']],
    ['origen' => 'tblRolSubModulo',       'dest' => 'tblrolsubmodulo',       'natural' => ['id_rol', 'id_submodulos']],
];

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function columnas(PDO $db, string $tabla, ?string $esquema = null): array
{
    $sql = 'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ?';
    $args = [$tabla];
    if ($esquema !== null) {
        $sql .= ' AND TABLE_SCHEMA = ?';
        $args[] = $esquema;
    }
    $st = $db->prepare($sql);
    $st->execute($args);
    $out = [];
    foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $c) {
        $out[strtolower($c)] = $c;
    }
    return $out;
}

function pkColumnas(PDO $db, string $tabla, ?string $esquema = null): array
{
    // MySQL y SQL Server nombran las PKs distinto ('PRIMARY' vs 'PK__...'),
    // por lo que se usa una consulta por motor.
    if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
        $sql = 'SELECT COLUMN_NAME
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = \'PRIMARY\'
                ORDER BY ORDINAL_POSITION';
        $st = $db->prepare($sql);
        $st->execute([$esquema, $tabla]);
    } else {
        $sql = 'SELECT kcu.COLUMN_NAME
                FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc
                JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu
                  ON kcu.CONSTRAINT_NAME = tc.CONSTRAINT_NAME
                 AND kcu.TABLE_NAME = tc.TABLE_NAME
                 AND kcu.TABLE_CATALOG = tc.TABLE_CATALOG
                WHERE tc.TABLE_NAME = ? AND tc.CONSTRAINT_TYPE = \'PRIMARY KEY\'
                ORDER BY kcu.ORDINAL_POSITION';
        $st = $db->prepare($sql);
        $st->execute([$tabla]);
    }
    return array_map('strtolower', $st->fetchAll(PDO::FETCH_COLUMN));
}

function id(string $driver, string $nombre): string
{
    return $driver === 'mysql' ? "`{$nombre}`" : "[{$nombre}]";
}

// ---------------------------------------------------------------------------
// Fase 2: copia de registros
// ---------------------------------------------------------------------------
$driverDest = $local->getAttribute(PDO::ATTR_DRIVER_NAME); // mysql
$insertados = [];
$omitidos = [];

try {
    $local->beginTransaction();

    foreach ($plan as $t) {
        $origen = $t['origen'];
        $dest = $t['dest'];
        $natural = $t['natural'];
        $fk = $fkMap[strtolower($dest)] ?? [];

        $colsO = columnas($prod, $origen);
        $colsD = columnas($local, $dest, $localConfig['database']);
        $pkO = pkColumnas($prod, $origen);
        $pkD = pkColumnas($local, $dest, $localConfig['database']);
        if (empty($pkO) || empty($pkD)) {
            throw new RuntimeException("{$origen}/{$dest}: sin llave primaria detectada");
        }

        $colsComunes = array_intersect_key($colsO, $colsD);

        // IDs ya presentes en local.
        $idsDest = [];
        foreach ($local->query('SELECT `' . $colsD[$pkD[0]] . '` FROM `' . $dest . '`') as $r) {
            $idsDest[(string) array_values($r)[0]] = true;
        }

        $candidatos = [];
        foreach ($prod->query('SELECT * FROM [' . $origen . ']') as $fila) {
            $filaL = array_change_key_case($fila, CASE_LOWER);
            $pkVal = (string) $filaL[$pkO[0]];
            if (isset($idsDest[$pkVal])) {
                continue;
            }
            $candidatos[] = $filaL;
        }

        if (empty($candidatos)) {
            echo "  = {$origen}: nada que sincronizar (local ya tiene " . count($idsDest) . " filas)\n";
            continue;
        }

        $colIns = [];
        foreach ($colsComunes as $lower => $nombreO) {
            $colIns[$lower] = $colsD[$lower];
        }
        $sql = 'INSERT INTO ' . id($driverDest, $dest)
            . ' (' . implode(', ', array_map(fn($c) => id($driverDest, $c), $colIns)) . ')'
            . ' VALUES (' . implode(', ', array_fill(0, count($colIns), '?')) . ')';
        $stmt = $local->prepare($sql);
        $ordenCols = array_keys($colIns);

        $n = 0;
        foreach ($candidatos as $filaL) {
            // 1) Filtro natural
            if (!empty($natural)) {
                $donde = [];
                $argsN = [];
                $sinNatural = false;
                foreach ($natural as $col) {
                    if (!isset($filaL[$col]) || $filaL[$col] === null || $filaL[$col] === ''
                        || !isset($colsD[$col])) {
                        $sinNatural = true;
                        break;
                    }
                    $donde[] = '`' . $colsD[$col] . '` = ?';
                    $argsN[] = $filaL[$col];
                }
                if (!$sinNatural) {
                    $st = $local->prepare('SELECT COUNT(*) FROM `' . $dest . '` WHERE ' . implode(' AND ', $donde));
                    $st->execute($argsN);
                    if ((int) $st->fetchColumn() > 0) {
                        $omitidos['duplicado-en-local'][$origen . ' #' . $filaL[$pkO[0]]] =
                            '(' . implode(', ', array_map(fn($c) => $c . '=' . $filaL[$c], $natural)) . ')';
                        continue;
                    }
                }
            }

            // 2) Huérfanos: padres inexistentes en local
            $problemas = [];
            foreach ($fk as $colOrigen => [$tablaPadre, $colPadre]) {
                if (!array_key_exists($colOrigen, $filaL)) {
                    continue;
                }
                $valor = $filaL[$colOrigen];
                if ($valor === null || $valor === '') {
                    continue;
                }
                $st = $local->prepare('SELECT COUNT(*) FROM `' . $tablaPadre . '` WHERE `' . $colPadre . '` = ?');
                $st->execute([$valor]);
                if ((int) $st->fetchColumn() === 0) {
                    $problemas[] = "{$colOrigen}={$valor} (padre inexistente: {$tablaPadre}.{$colPadre})";
                }
            }
            if (!empty($problemas)) {
                $omitidos['huerfano-en-local'][$origen . ' #' . $filaL[$pkO[0]]] = implode('; ', $problemas);
                continue;
            }

            // 3) Insertar
            $vals = [];
            foreach ($ordenCols as $lower) {
                $vals[] = $filaL[$lower] ?? null;
            }
            $stmt->execute($vals);
            $n++;
        }

        $insertados[$origen] = $n;
        echo "  ✓ {$origen} → {$dest}: {$n} registros insertados de " . count($candidatos) . " candidatos\n";
    }

    $local->commit();
    echo "\n  ✓ TRANSACCIÓN CONFIRMADA (commit)\n";
} catch (Throwable $e) {
    if ($local->inTransaction()) {
        $local->rollBack();
    }
    fwrite(STDERR, "\n  ✗ ERROR: " . $e->getMessage() . "\n  ✗ TRANSACCIÓN REVERTIDA (rollback)\n");
    exit(1);
}

// ---------------------------------------------------------------------------
// Resumen
// ---------------------------------------------------------------------------
echo "\n=== RESUMEN ===\n";
$total = 0;
foreach ($insertados as $tabla => $n) {
    $total += $n;
    echo "  insertados  {$tabla}: {$n}\n";
}
echo "  TOTAL INSERTADOS: {$total}\n";

foreach ($omitidos as $motivo => $filas) {
    echo "\n  Omitidos ({$motivo}): " . count($filas) . "\n";
    foreach ($filas as $clave => $detalle) {
        echo "    - {$clave}: {$detalle}\n";
    }
}

if (empty($insertados) && empty($omitidos)) {
    echo "  Nada que sincronizar: local ya está al día.\n";
}
