<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

echo 'Registros en tblVehiculo: '.DB::table('tblVehiculo')->count().PHP_EOL;
$datos = DB::table('tblVehiculo')->limit(3)->get();
foreach ($datos as $d) {
    echo json_encode($d).PHP_EOL;
}
