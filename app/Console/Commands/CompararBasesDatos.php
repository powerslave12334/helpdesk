<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CompararBasesDatos extends Command
{
    protected $signature = 'db:comparar';
    protected $description = 'Comparar esquema de BD local (MySQL) con productivo (SQL Server)';

    public function handle()
    {
        $this->line('=== COMPARACIÓN DE BASES DE DATOS ===');
        $this->newLine();

        // Conectar a productivo
        $this->line('Conectando a productivo (SQL Server)...');
        try {
            $tablesProductivo = DB::connection('sqlsrv_productivo')
                ->select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME");
            $tablesProductivo = array_map(fn($t) => strtolower($t->TABLE_NAME), $tablesProductivo);
            $this->info('Tablas en productivo: ' . count($tablesProductivo));
        } catch (\Exception $e) {
            $this->error('Error conectando a productivo: ' . $e->getMessage());
            return 1;
        }

        // Conectar a local
        $this->line('Conectando a local (MySQL)...');
        $tablesLocal = DB::connection('mysql')->select('SHOW TABLES');
        $tablesLocal = array_map(fn($t) => strtolower(reset($t)), $tablesLocal);
        $this->info('Tablas en local: ' . count($tablesLocal));
        $this->newLine();

        // Comparar tablas
        $tablasEnProductivo = array_diff($tablesProductivo, $tablesLocal);
        $tablasEnLocal = array_diff($tablesLocal, $tablesProductivo);

        if (empty($tablasEnProductivo) && empty($tablasEnLocal)) {
            $this->info('✅ Las bases de datos tienen las mismas tablas.');
        } else {
            if (!empty($tablasEnProductivo)) {
                $this->error('❌ Tablas en productivo que NO están en local:');
                foreach ($tablasEnProductivo as $t) $this->line("   - $t");
            }
            if (!empty($tablasEnLocal)) {
                $this->error('❌ Tablas en local que NO están en productivo:');
                foreach ($tablasEnLocal as $t) $this->line("   - $t");
            }
        }
        $this->newLine();

        // Comparar cantidad de registros
        $this->line('=== COMPARACIÓN DE REGISTROS ===');
        $this->newLine();

        $tablasComunes = array_intersect($tablesProductivo, $tablesLocal);
        $diferenciasRegistros = [];

        $this->table(
            ['Tabla', 'Productivo', 'Local', 'Diferencia'],
            []
        );

        $rows = [];
        foreach ($tablasComunes as $table) {
            try {
                // Saltar tablas de sistema
                if (in_array($table, ['sysdiagrams', 'migrations'])) continue;

                // Contar en productivo (SQL Server)
                $countP = DB::connection('sqlsrv_productivo')->selectOne("SELECT COUNT(*) as total FROM [$table]")->total;

                // Contar en local (MySQL)
                $countL = DB::connection('mysql')->selectOne("SELECT COUNT(*) as total FROM `$table`")->total;

                $diferencia = $countP - $countL;
                $diferenciaStr = $diferencia > 0 ? "+$diferencia" : (string) $diferencia;

                $rows[] = [
                    $table,
                    number_format($countP),
                    number_format($countL),
                    $diferencia == 0 ? '✅' : "❌ $diferenciaStr",
                ];

                if ($diferencia != 0) {
                    $diferenciasRegistros[$table] = [
                        'productivo' => $countP,
                        'local' => $countL,
                        'diferencia' => $diferencia,
                    ];
                }
            } catch (\Exception $e) {
                $rows[] = [$table, 'ERROR', 'ERROR', $e->getMessage()];
            }
        }

        $this->table(['Tabla', 'Productivo', 'Local', 'Diferencia'], $rows);

        // Resumen
        $this->newLine();
        if (empty($diferenciasRegistros)) {
            $this->info('✅ Todas las tablas tienen la misma cantidad de registros.');
        } else {
            $this->warn("⚠️  Hay " . count($diferenciasRegistros) . " tablas con diferencias de registros:");
            foreach ($diferenciasRegistros as $table => $info) {
                $this->line("   - $table: productivo={$info['productivo']}, local={$info['local']}, diferencia=" . ($info['diferencia'] > 0 ? "+" : "") . "{$info['diferencia']}");
            }
        }

        $this->newLine();
        $this->info('=== FIN DE LA COMPARACIÓN ===');
        return 0;
    }

    private function normalizarTipo(string $tipo): string
    {
        $tipo = strtolower($tipo);
        return match(true) {
            str_contains($tipo, 'varchar') => 'string',
            str_contains($tipo, 'nvarchar') => 'string',
            str_contains($tipo, 'char') => 'string',
            str_contains($tipo, 'int') => 'integer',
            str_contains($tipo, 'bigint') => 'bigint',
            str_contains($tipo, 'smallint') => 'smallint',
            str_contains($tipo, 'tinyint') => 'tinyint',
            str_contains($tipo, 'bit') => 'boolean',
            str_contains($tipo, 'datetime') => 'datetime',
            str_contains($tipo, 'date') => 'date',
            str_contains($tipo, 'time') => 'time',
            str_contains($tipo, 'decimal') => 'decimal',
            str_contains($tipo, 'float') => 'float',
            str_contains($tipo, 'real') => 'float',
            str_contains($tipo, 'text') => 'text',
            str_contains($tipo, 'ntext') => 'text',
            str_contains($tipo, 'image') => 'binary',
            default => $tipo,
        };
    }

    private function extraerTipoMysql(string $type): string
    {
        $type = strtolower($type);
        if (str_contains($type, 'int')) return 'integer';
        if (str_contains($type, 'varchar')) return 'string';
        if (str_contains($type, 'text')) return 'text';
        if (str_contains($type, 'datetime')) return 'datetime';
        if (str_contains($type, 'date')) return 'date';
        if (str_contains($type, 'decimal')) return 'decimal';
        if (str_contains($type, 'float')) return 'float';
        if (str_contains($type, 'tinyint') || str_contains($type, 'smallint')) return 'integer';
        return $type;
    }
}
