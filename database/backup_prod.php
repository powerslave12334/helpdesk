<?php
/**
 * Respaldo previo de PRODUCCIÓN (SQL Server 192.168.11.20 / HELPDESK)
 *
 * Genera un archivo .sql con INSERTs (IDs explícitos) de todas las tablas
 * de negocio, re-ejecutable para restaurar sobre SQL Server.
 *
 * Ejecutar: php database/backup_prod.php
 * Salida:   database/backups/prod_AAAA-MM-DD_HHMMSS.sql (ignorado por git)
 */

$prodConfig = [
    'host' => '192.168.11.20',
    'port' => '1433',
    'database' => 'HELPDESK',
    'username' => 'sa',
    'password' => 'System$2026*',
];

// Objetos internos de SQL Server que no forman parte del negocio.
$ignorar = ['sysdiagrams'];

echo "=== Respaldo de PRODUCCIÓN (HELPDESK) ===\n";

$prod = new PDO(
    "sqlsrv:Server={$prodConfig['host']},{$prodConfig['port']};Database={$prodConfig['database']}",
    $prodConfig['username'],
    $prodConfig['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
echo "  ✓ Conectado a {$prodConfig['host']}/{$prodConfig['database']}\n";

$dir = __DIR__ . '/backups';
if (!is_dir($dir)) {
    mkdir($dir, 0775, true);
}
$archivo = $dir . '/prod_' . date('Ymd_His') . '.sql';

// Tablas de negocio
$tablas = [];
foreach ($prod->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME") as $r) {
    if (!in_array($r['TABLE_NAME'], $ignorar, true)) {
        $tablas[] = $r['TABLE_NAME'];
    }
}
echo '  Tablas a respaldar: ' . count($tablas) . "\n";

$fh = fopen($archivo, 'w');
if (!$fh) {
    fwrite(STDERR, "  ✗ No se pudo crear {$archivo}\n");
    exit(1);
}

// Formatea un valor devuelto por SQL Server como literal T-SQL.
function valorSql(string $tipo, $v): string
{
    if ($v === null) {
        return 'NULL';
    }
    $tipo = strtolower($tipo);

    if (in_array($tipo, ['varbinary', 'binary', 'image', 'rowversion', 'timestamp'], true)) {
        return '0x' . bin2hex((string) $v);
    }
    if ($tipo === 'bit') {
        return ($v === true || $v === 1 || $v === '1') ? '1' : '0';
    }
    if (in_array($tipo, ['int', 'bigint', 'smallint', 'tinyint', 'decimal', 'numeric', 'float', 'real', 'money', 'smallmoney'], true)) {
        $s = is_bool($v) ? ($v ? '1' : '0') : (string) $v;
        return preg_match('/^-?\d+(\.\d+)?([eE][+-]?\d+)?$/', $s) ? $s : '0';
    }
    if ($v instanceof DateTimeInterface) {
        return "'" . $v->format('Y-m-d H:i:s.v') . "'";
    }
    if (in_array($tipo, ['date', 'datetime', 'datetime2', 'datetimeoffset', 'smalldatetime', 'time'], true)) {
        return "'" . str_replace("'", "''", (string) $v) . "'";
    }
    // Cadenas (varchar/nvarchar/text/char...)
    return "N'" . str_replace("'", "''", (string) $v) . "'";
}

// UTF-8 con BOM para que SSMS/sqlcmd abran bien los acentos.
fwrite($fh, "\xEF\xBB\xBF");
fwrite($fh, "-- Respaldo de PRODUCCIÓN: HELPDESK @ {$prodConfig['host']}\n");
fwrite($fh, '-- Generado: ' . date('Y-m-d H:i:s') . "\n");
fwrite($fh, "-- Restaurar:  sqlcmd -S {$prodConfig['host']},{$prodConfig['port']} -d {$prodConfig['database']} -i archivo.sql\n");
fwrite($fh, "-- NOTA: los INSERT llevan IDs explícitos; ejecutar sobre tablas vacías\n");
fwrite($fh, "--       (o habilitar SET IDENTITY_INSERT por tabla, ya incluido abajo).\n");
fwrite($fh, "SET NOCOUNT ON;\n\n");

$totalFilas = 0;

foreach ($tablas as $tabla) {
    // Columnas en orden
    $cols = [];
    $st = $prod->prepare("SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? ORDER BY ORDINAL_POSITION");
    $st->execute([$tabla]);
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $cols[$r['COLUMN_NAME']] = $r['DATA_TYPE'];
    }
    if (empty($cols)) {
        echo "  ! {$tabla}: sin columnas, se omite\n";
        continue;
    }

    // ¿Llave identidad?
    $st = $prod->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND COLUMNPROPERTY(OBJECT_ID(TABLE_NAME), COLUMN_NAME, 'IsIdentity') = 1");
    $st->execute([$tabla]);
    $identidad = $st->fetchColumn();

    $nombres = array_keys($cols);
    $lista = '[' . implode('], [', $nombres) . ']';

    if ($identidad) {
        fwrite($fh, "SET IDENTITY_INSERT [{$tabla}] ON;\n");
    }

    $n = 0;
    $stmt = $prod->query("SELECT * FROM [{$tabla}]");
    while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $vals = [];
        foreach ($nombres as $c) {
            $vals[] = valorSql($cols[$c], $fila[$c] ?? null);
        }
        fwrite($fh, "INSERT INTO [{$tabla}] ({$lista}) VALUES (" . implode(', ', $vals) . ");\n");
        $n++;
    }

    if ($identidad) {
        fwrite($fh, "SET IDENTITY_INSERT [{$tabla}] OFF;\n");
    }
    fwrite($fh, "\n");

    $totalFilas += $n;
    echo "  ✓ {$tabla}: {$n} filas\n";
}

fclose($fh);

$kb = round(filesize($archivo) / 1024, 1);
echo "\n  Respaldo completo: {$totalFilas} filas, {$kb} KB\n";
echo "  Archivo: {$archivo}\n";
