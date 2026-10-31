<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PeriodoMantenimientoSeeder extends Seeder
{
    public function run(): void
    {
        $periodos = [
            ['descripcion' => 'Mensual', 'meses' => 1, 'activo' => true],
            ['descripcion' => 'Bimestral', 'meses' => 2, 'activo' => true],
            ['descripcion' => 'Trimestral', 'meses' => 3, 'activo' => true],
            ['descripcion' => 'Semestral', 'meses' => 6, 'activo' => true],
            ['descripcion' => 'Anual', 'meses' => 12, 'activo' => true],
        ];

        foreach ($periodos as $periodo) {
            $exists = DB::table('tblPeriodoMantenimiento')
                ->where('meses', $periodo['meses'])
                ->exists();

            if (!$exists) {
                DB::table('tblPeriodoMantenimiento')->insert($periodo);
            }
        }
    }
}
