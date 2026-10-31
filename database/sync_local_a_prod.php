<?php
/**
 * Sincronización LOCAL (MySQL) → PRODUCCIÓN (SQL Server)
 *
 * Inserta en producción únicamente los registros que faltan (insert-if-missing).
 * Nunca borra ni sobreescribe registros existentes.
 * Transacción con rollback ante cualquier error. Re-ejecutable.
 *
 * Reglas:
 *  - 'natural'  => además de faltar por ID, se omite si el destino ya tiene
 *                  una fila con el mismo contenido natural (evita duplicados).
 *  - Huérfanos  => se omiten filas cuyo padre (FK lógica) no existe en el
 *                  destino; se listan al final.
 *
 * Ejecutar: php database/sync_local_a_prod.php
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

echo "=== Sincronización LOCAL → PRODUCCIÓN (insert-if-missing) ===\n";

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
echo "  ✓ Local (MySQL) y Producción (SQL Server) conectados\n\n";

// ---------------------------------------------------------------------------
// FK lógicas (definidas por el esquema local); se validan contra el destino.
// Claves: tabla en minúsculas => columna (minúsculas) => [tabla padre, columna padre]
// ---------------------------------------------------------------------------
$fkMap = [
    'tblsubmodulos' => ['id_modulos' => ['tblModulos', 'id_Modulos']],
    'tblhistorialequipousuario' => [
        'id_equipo' => ['tblEquipo', 'id_Equipo'],
        'id_usuario' => ['tblUsuario', 'id_Usuario'],
        'id_area' => ['tblArea', 'id_Area'],
        'id_usuarioaccion' => ['tblUsuario', 'id_Usuario'],
        'id_equipousuariooriginal' => ['tblEquipoUsuario', 'id_EquipoUsuario'],
    ],
    'tblrolesusuarios' => [
        'id_rol' => ['tblRoles', 'id_Rol'],
        'id_usuario' => ['tblUsuario', 'id_Usuario'],
    ],
    'tblequipo' => [
        'id_estatus' => ['tblEstatus', 'id_Estatus'],
        'id_tipoequipo' => ['tblTipoEquipo', 'id_TipoEquipo'],
        'id_oficinas' => ['tblOficinas', 'id_Oficinas'],
    ],
    'tblusuario' => [
        'id_area' => ['tblArea', 'id_Area'],
        'id_depto' => ['tblDepto', 'id_Depto'],
        'id_oficinas' => ['tblOficinas', 'id_Oficinas'],
        'id_tipoempleado' => ['tblTipoEmpleado', 'id_TipoEmpleado'],
    ],
    'tbllogin' => ['id_usuario' => ['tblUsuario', 'id_Usuario']],
    'tblnotificaciones' => ['id_usuario' => ['tblUsuario', 'id_Usuario']],
    'tblequipousuario' => [
        'id_equipo' => ['tblEquipo', 'id_Equipo'],
        'id_usuario' => ['tblUsuario', 'id_Usuario'],
        'id_area' => ['tblArea', 'id_Area'],
    ],
];

// Nota: en tblNotificaciones la columna es id_UsuarioDestino (minúsculas:
// id_usuariodestino); se agrega aparte para no confundir con id_Usuario.
$fkMap['tblnotificaciones'] = ['id_usuariodestino' => ['tblUsuario', 'id_Usuario']];

// Tablas a sincronizar, en orden (padres primero).
$plan = [
    ['origen' => 'tblsubmodulos',               'dest' => 'tblSubModulos',            'natural' => ['id_modulos', 'nombre']],
    ['origen' => 'tblconfiguracionsistema',     'dest' => 'tblConfiguracionSistema',  'natural' => ['clave']],
    ['origen' => 'tblequipo',                   'dest' => 'tblEquipo',                'natural' => ['numeroserie']],
    ['origen' => 'tblhistorialequipousuario',   'dest' => 'tblHistorialEquipoUsuario','natural' => []],
    ['origen' => 'tblvehiculo',                 'dest' => 'tblVehiculo',              'natural' => []],
    ['origen' => 'tblrolesusuarios',            'dest' => 'tblRolesUsuarios',         'natural' => ['id_rol', 'id_usuario']],
    ['origen' => 'tblequipousuario',            'dest' => 'tblEquipoUsuario',         'natural' => ['id_equipo', 'id_usuario']],
    ['origen' => 'tblnotificaciones',           'dest' => 'tblNotificaciones',        'natural' => []],
    ['origen' => 'tblusuario',                  'dest' => 'tblUsuario',               'natural' => ['correo']],
    ['origen' => 'tbllogin',                    'dest' => 'tblLogin',                 'natural' => ['usuario']],
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

function esIdentity(PDO $db, string $tabla): bool
{
    $st = $db->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND COLUMNPROPERTY(OBJECT_ID(TABLE_NAME), COLUMN_NAME, 'IsIdentity') = 1");
    $st->execute([$tabla]);
    return (bool) $st->fetchColumn();
}

function id(string $driver, string $nombre): string
{
    return $driver === 'mysql' ? "`{$nombre}`" : "[{$nombre}]";
}

// ---------------------------------------------------------------------------
// Sincronización
// ---------------------------------------------------------------------------

$driverDest = $prod->getAttribute(PDO::ATTR_DRIVER_NAME); // sqlsrv
$insertados = [];
$omitidos = []; // motivo => [detalle, ...]

try {
    $prod->beginTransaction();

    foreach ($plan as $t) {
        $origen = $t['origen'];
        $dest = $t['dest'];
        $natural = $t['natural'];
        $fk = $fkMap[strtolower($origen)] ?? [];

        $colsO = columnas($local, $origen, $localConfig['database']);
        $colsD = columnas($prod, $dest);
        $pkO = pkColumnas($local, $origen, $localConfig['database']);
        $pkD = pkColumnas($prod, $dest);
        if (empty($pkO) || empty($pkD)) {
            throw new RuntimeException("{$origen}: sin llave primaria detectada");
        }

        // Columnas comunes (nombre real en el destino).
        $colsComunes = array_intersect_key($colsO, $colsD);

        // IDs ya presentes en el destino.
        $idsDest = [];
        foreach ($prod->query('SELECT ' . id($driverDest, $colsD[$pkD[0]]) . ' FROM ' . id($driverDest, $dest)) as $r) {
            $idsDest[(string) array_values($r)[0]] = true;
        }

        $candidatos = [];
        foreach ($local->query('SELECT * FROM `' . $origen . '`') as $fila) {
            $filaL = array_change_key_case($fila, CASE_LOWER);
            $pkVal = (string) $filaL[$pkO[0]];
            if (isset($idsDest[$pkVal])) {
                continue; // ya existe en producción
            }
            $candidatos[] = $filaL;
        }

        if (empty($candidatos)) {
            echo "  = {$origen}: nada que sincronizar (producción ya tiene " . count($idsDest) . " filas)\n";
            continue;
        }

        // Construir INSERT
        $colIns = [];
        foreach ($colsComunes as $lower => $nombreO) {
            $colIns[$lower] = $colsD[$lower];
        }
        $sql = 'INSERT INTO ' . id($driverDest, $dest)
            . ' (' . implode(', ', array_map(fn($c) => id($driverDest, $c), $colIns)) . ')'
            . ' VALUES (' . implode(', ', array_fill(0, count($colIns), '?')) . ')';
        $stmt = $prod->prepare($sql);
        $ordenCols = array_keys($colIns);

        $identityOn = esIdentity($prod, $dest);
        if ($identityOn) {
            $prod->exec('SET IDENTITY_INSERT ' . id($driverDest, $dest) . ' ON');
        }

        $n = 0;
        foreach ($candidatos as $filaL) {
            // 1) Filtro natural: ¿el destino ya tiene esta fila por contenido?
            if (!empty($natural)) {
                $donde = [];
                $argsN = [];
                $sinNatural = false;
                foreach ($natural as $col) {
                    if (!isset($filaL[$col]) || $filaL[$col] === null || $filaL[$col] === '') {
                        $sinNatural = true;
                        break; // sin valor: no se puede comparar, se pasa
                    }
                    if (!isset($colsD[$col])) {
                        $sinNatural = true;
                        break;
                    }
                    $donde[] = id($driverDest, $colsD[$col]) . ' = ?';
                    $argsN[] = $filaL[$col];
                }
                if (!$sinNatural) {
                    $st = $prod->prepare('SELECT COUNT(*) FROM ' . id($driverDest, $dest) . ' WHERE ' . implode(' AND ', $donde));
                    $st->execute($argsN);
                    if ((int) $st->fetchColumn() > 0) {
                        $omitidos['duplicado-en-prod'][$origen . ' #' . $filaL[$pkO[0]]] =
                            '(' . implode(', ', array_map(fn($c) => $c . '=' . $filaL[$c], $natural)) . ')';
                        continue;
                    }
                }
            }

            // 2) Huérfanos: padres inexistentes en producción
            $problemas = [];
            foreach ($fk as $colOrigen => [$tablaPadre, $colPadre]) {
                if (!array_key_exists($colOrigen, $filaL)) {
                    continue;
                }
                $valor = $filaL[$colOrigen];
                if ($valor === null || $valor === '') {
                    continue; // nullable
                }
                if (!isset($colsD[$colOrigen])) {
                    $problemas[] = "{$colOrigen} (columna ausente en prod)";
                    continue;
                }
                $st = $prod->prepare('SELECT COUNT(*) FROM ' . id($driverDest, $tablaPadre) . ' WHERE ' . id($driverDest, $colPadre) . ' = ?');
                $st->execute([$valor]);
                if ((int) $st->fetchColumn() === 0) {
                    $problemas[] = "{$colOrigen}={$valor} (padre inexistente: {$tablaPadre}.{$colPadre})";
                }
            }
            if (!empty($problemas)) {
                $omitidos['huerfano-en-prod'][$origen . ' #' . $filaL[$pkO[0]]] = implode('; ', $problemas);
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

        if ($identityOn) {
            $prod->exec('SET IDENTITY_INSERT ' . id($driverDest, $dest) . ' OFF');
        }

        $insertados[$origen] = $n;
        echo "  ✓ {$origen}: {$n} registros insertados de " . count($candidatos) . " candidatos\n";
    }

    // -------------------------------------------------------------------------
    // migrations: historial de migraciones (el archivo 000003 ya no existe en
    // el repo; se replica el registro para que ambas tablas queden iguales).
    // -------------------------------------------------------------------------
    $mig = '2026_09_28_000003_add_tipo_opciones_to_tblEquipoCampo';
    $fila = $local->query("SELECT batch FROM migrations WHERE migration = '" . $mig . "'")->fetch(PDO::FETCH_ASSOC);
    if ($fila) {
        $st = $prod->prepare('SELECT COUNT(*) FROM migrations WHERE migration = ?');
        $st->execute([$mig]);
        if ((int) $st->fetchColumn() === 0) {
            $prod->prepare('INSERT INTO migrations (migration, batch) VALUES (?, ?)')->execute([$mig, $fila['batch']]);
            $insertados['migrations'] = 1;
            echo "  ✓ migrations: registro '{$mig}' insertado (batch {$fila['batch']})\n";
        } else {
            echo "  = migrations: el registro ya existe\n";
        }
    }

    $prod->commit();
    echo "\n  ✓ TRANSACCIÓN CONFIRMADA (commit)\n";
} catch (Throwable $e) {
    try {
        $prod->exec('SET IDENTITY_INSERT ' . id($driverDest, $t['dest'] ?? '') . ' OFF');
    } catch (Throwable $ignorada) {
    }
    if ($prod->inTransaction()) {
        $prod->rollBack();
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
    echo "  Nada que sincronizar: producción ya está al día.\n";
}
