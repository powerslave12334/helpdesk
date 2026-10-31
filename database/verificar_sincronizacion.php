<?php
/**
 * Verificación de sincronización LOCAL (MySQL) ↔ PRODUCCIÓN (SQL Server)
 * Solo lectura: comparte tablas, conteos, IDs en ambas direcciones,
 * pares lógicos de catálogos de permisos y el historial de migraciones.
 *
 * Ejecutar: php database/verificar_sincronizacion.php
 */

$localConfig = ['host' => '127.0.0.1', 'port' => '3306', 'database' => 'helpdesk', 'username' => 'root', 'password' => ''];
$prodConfig = ['host' => '192.168.11.20', 'port' => '1433', 'database' => 'HELPDESK', 'username' => 'sa', 'password' => 'System$2026*'];

$loc = new PDO(
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

$tl = [];
foreach ($loc->query("SELECT TABLE_NAME t FROM information_schema.TABLES WHERE TABLE_SCHEMA = '{$localConfig['database']}' AND TABLE_TYPE = 'BASE TABLE'") as $r) {
    $tl[strtolower($r['t'])] = $r['t'];
}
$tp = [];
foreach ($prod->query("SELECT TABLE_NAME t FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'") as $r) {
    $tp[strtolower($r['t'])] = $r['t'];
}
unset($tp['sysdiagrams']); // objeto interno de SQL Server
$comunes = array_values(array_intersect_key($tl, $tp));
$soloLocal = array_diff_key($tl, $tp);
$soloProd = array_diff_key($tp, $tl);

echo "== COBERTURA DE TABLAS ==\n";
echo '  comunes: ' . count($comunes)
    . ' | solo local: ' . (count($soloLocal) ? implode(', ', $soloLocal) : 'ninguna')
    . ' | solo prod: ' . (count($soloProd) ? implode(', ', $soloProd) : 'ninguna') . "\n";

echo "\n== CONTEOS QUE DIFIEREN ==\n";
$difiere = 0;
foreach ($comunes as $t) {
    $cl = (int) $loc->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
    $cp = (int) $prod->query("SELECT COUNT(*) FROM [$t]")->fetchColumn();
    if ($cl !== $cp) {
        printf("  %-32s local=%-5d prod=%-5d (local-prod=%+d)\n", $t, $cl, $cp, $cl - $cp);
        $difiere++;
    }
}
if (!$difiere) {
    echo "  (ninguna)\n";
}

echo "\n== IDs QUE DIFIEREN EN AMBAS DIRECCIONES ==\n";
foreach ($comunes as $t) {
    $pk = null;
    foreach ($loc->query("SELECT COLUMN_NAME c FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = '{$localConfig['database']}' AND TABLE_NAME = '$t' AND CONSTRAINT_NAME = 'PRIMARY' ORDER BY ORDINAL_POSITION") as $r) {
        $pk = $r['c'];
        break;
    }
    if (!$pk) {
        continue;
    }
    $L = [];
    foreach ($loc->query("SELECT `$pk` id FROM `$t`") as $r) {
        $L[(string) $r['id']] = 1;
    }
    $P = [];
    foreach ($prod->query("SELECT [$pk] id FROM [$t]") as $r) {
        $P[(string) $r['id']] = 1;
    }
    $pNoL = array_diff_key($P, $L);
    $lNoP = array_diff_key($L, $P);
    if ($pNoL || $lNoP) {
        $a = $pNoL ? implode(',', array_slice(array_keys($pNoL), 0, 25)) : '-';
        $b = $lNoP ? implode(',', array_slice(array_keys($lNoP), 0, 25)) : '-';
        printf("  %-32s prod-no-local(%d): %s | local-no-prod(%d): %s\n", $t, count($pNoL), $a, count($lNoP), $b);
    }
}

echo "\n== PARES LOGICOS (catalogos de permisos) ==\n";
$pares = [
    ['tblrolsubmodulo', 'tblRolSubModulo', 'id_Rol', 'id_SubModulos'],
    ['tblrolmodulo', 'tblRolModulo', 'id_Rol', 'id_Modulos'],
    ['tblrolesusuarios', 'tblRolesUsuarios', 'id_Rol', 'id_Usuario'],
    ['tblsubmodulos', 'tblSubModulos', 'id_Modulos', 'nombre'],
];
foreach ($pares as $p) {
    $a = [];
    foreach ($loc->query("SELECT `{$p[2]}` a, `{$p[3]}` b FROM `{$p[0]}`") as $r) {
        $a[$r['a'] . '|' . $r['b']] = 1;
    }
    $b = [];
    foreach ($prod->query("SELECT [{$p[2]}] a, [{$p[3]}] b FROM [{$p[1]}]") as $r) {
        $b[$r['a'] . '|' . $r['b']] = 1;
    }
    $sL = count(array_diff_key($a, $b));
    $sP = count(array_diff_key($b, $a));
    printf("  %-20s pares local=%d prod=%d | solo-local=%d solo-prod=%d  %s\n",
        $p[0], count($a), count($b), $sL, $sP, ($sL == 0 && $sP == 0) ? 'OK' : '*** DIFIERE ***');
}

echo "\n== MIGRATIONS (por nombre) ==\n";
$ml = [];
foreach ($loc->query('SELECT migration FROM migrations') as $r) {
    $ml[$r['migration']] = 1;
}
$mp = [];
foreach ($prod->query('SELECT migration FROM migrations') as $r) {
    $mp[$r['migration']] = 1;
}
echo '  local=' . count($ml) . ' prod=' . count($mp)
    . ' | faltan en prod: ' . count(array_diff_key($ml, $mp))
    . ' | sobran en prod: ' . count(array_diff_key($mp, $ml)) . "\n";

echo "\n== TABLAS LEGACY EN LOCAL ==\n";
foreach (['tblAcciones', 'tblEquipoCampo', 'tblTipoEquipoCampo', 'tblEquipoDetalle', 'tblRolPermiso', 'tblPermisoUsuarios'] as $t) {
    $cl = (int) $loc->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
    $cp = (int) $prod->query("SELECT COUNT(*) FROM [$t]")->fetchColumn();
    printf("  %-24s local=%-5d prod=%-5d %s\n", $t, $cl, $cp, $cl === $cp ? 'OK' : '*** DIFIERE ***');
}
