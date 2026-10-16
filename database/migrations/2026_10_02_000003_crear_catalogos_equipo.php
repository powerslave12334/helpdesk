<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tblCatMarca', function (Blueprint $table) {
            $table->unsignedBigInteger('id_Marca')->autoIncrement();
            $table->string('nombre', 100)->unique();
            $table->dateTime('fechaAlta');
            $table->boolean('activo')->default(true);
        });

        Schema::create('tblCatModelo', function (Blueprint $table) {
            $table->unsignedBigInteger('id_Modelo')->autoIncrement();
            $table->unsignedBigInteger('id_Marca');
            $table->string('nombre', 100);
            $table->dateTime('fechaAlta');
            $table->boolean('activo')->default(true);
            $table->unique(['id_Marca', 'nombre']);
            $table->foreign('id_Marca')->references('id_Marca')->on('tblCatMarca');
        });

        Schema::create('tblCatProcesador', function (Blueprint $table) {
            $table->unsignedBigInteger('id_Procesador')->autoIncrement();
            $table->string('nombre', 100)->unique();
            $table->dateTime('fechaAlta');
            $table->boolean('activo')->default(true);
        });

        Schema::create('tblCatSistemaOperativo', function (Blueprint $table) {
            $table->unsignedBigInteger('id_SistemaOperativo')->autoIncrement();
            $table->string('nombre', 100)->unique();
            $table->dateTime('fechaAlta');
            $table->boolean('activo')->default(true);
        });

        Schema::create('tblCatTipoAlmacenamiento', function (Blueprint $table) {
            $table->unsignedBigInteger('id_TipoAlmacenamiento')->autoIncrement();
            $table->string('nombre', 100)->unique();
            $table->dateTime('fechaAlta');
            $table->boolean('activo')->default(true);
        });

        $this->sembrar();
    }

    public function down(): void
    {
        Schema::dropIfExists('tblCatTipoAlmacenamiento');
        Schema::dropIfExists('tblCatSistemaOperativo');
        Schema::dropIfExists('tblCatProcesador');
        Schema::dropIfExists('tblCatModelo');
        Schema::dropIfExists('tblCatMarca');
    }

    /**
     * Semilla inicial: valores de referencia + los valores ya usados en
     * equipos existentes (para que la edición de equipos actuales no pierda
     * opciones). Todo se gestiona después desde Configuración > Catálogos de
     * Equipo.
     */
    private function sembrar(): void
    {
        $ahora = now();

        // --- Marcas ---
        $marcas = $this->unicos(array_merge([
            'HP', 'Dell', 'Lenovo', 'ASUS', 'Acer', 'MSI', 'Gigabyte', 'Samsung',
            'LG', 'ViewSonic', 'Brother', 'Epson', 'Canon', 'TP-Link', 'Logitech',
            'Genérico',
        ], $this->existentes('marca')));

        $idsMarcas = [];
        foreach ($marcas as $nombre) {
            $idsMarcas[strtolower($nombre)] = DB::table('tblCatMarca')->insertGetId([
                'nombre' => $nombre,
                'fechaAlta' => $ahora,
                'activo' => 1,
            ]);
        }

        // --- Modelos (por marca) ---
        $modelos = [
            'HP' => ['ProDesk', 'EliteDesk', 'Pavilion', 'Compaq'],
            'Dell' => ['OptiPlex', 'Latitude', 'Inspiron', 'Vostro', 'XPS'],
            'Lenovo' => ['ThinkCentre', 'ThinkPad', 'IdeaPad', 'IdeaCentre'],
            'ASUS' => ['ExpertCenter', 'VivoBook', 'ZenBook', 'ROG'],
            'Acer' => ['Aspire', 'Veriton', 'TravelMate'],
            'MSI' => ['Modern', 'Prestige'],
            'Samsung' => ['Galaxy'],
            'Genérico' => ['Genérico', 'Ensamblado'],
        ];
        $vistos = [];
        foreach (DB::table('tblequipo')->select('marca', 'modelo')->distinct()->get() as $fila) {
            $marca = trim((string) $fila->marca);
            $modelo = trim((string) $fila->modelo);
            if ($marca === '' || $modelo === '') {
                continue;
            }
            $modelos[$marca][] = $modelo;
        }
        foreach ($modelos as $marca => $nombres) {
            $idMarca = $idsMarcas[strtolower(trim($marca))] ?? null;
            if ($idMarca === null) {
                continue;
            }
            foreach ($this->unicos($nombres) as $nombre) {
                $llave = $idMarca . '|' . strtolower($nombre);
                if (isset($vistos[$llave])) {
                    continue;
                }
                $vistos[$llave] = true;
                DB::table('tblCatModelo')->insert([
                    'id_Marca' => $idMarca,
                    'nombre' => $nombre,
                    'fechaAlta' => $ahora,
                    'activo' => 1,
                ]);
            }
        }

        // --- Procesadores ---
        $this->sembrarSimples('tblCatProcesador', array_merge([
            'Intel Core i3', 'Intel Core i5', 'Intel Core i7', 'Intel Core i9',
            'Intel Pentium', 'Intel Celeron', 'AMD Ryzen 3', 'AMD Ryzen 5',
            'AMD Ryzen 7', 'AMD Ryzen 9', 'AMD Athlon', 'Genérico',
        ], $this->existentes('procesador')), $ahora);

        // --- Sistemas operativos ---
        $this->sembrarSimples('tblCatSistemaOperativo', array_merge([
            'Windows 10', 'Windows 11', 'Windows Server', 'Linux Ubuntu',
            'Linux', 'Sin sistema operativo', 'Genérico',
        ], $this->existentes('sistemaOperativo')), $ahora);

        // --- Tipos de almacenamiento ---
        $this->sembrarSimples('tblCatTipoAlmacenamiento', [
            'HDD', 'SSD SATA', 'SSD NVMe', 'eMMC', 'Otro',
        ], $ahora);
    }

    private function sembrarSimples(string $tabla, array $valores, $ahora): void
    {
        foreach ($this->unicos($valores) as $nombre) {
            DB::table($tabla)->insert([
                'nombre' => $nombre,
                'fechaAlta' => $ahora,
                'activo' => 1,
            ]);
        }
    }

    /** Valores distintos y no vacíos de una columna de tblequipo. */
    private function existentes(string $columna): array
    {
        return DB::table('tblequipo')->distinct()->pluck($columna)
            ->map(fn ($v) => trim((string) $v))
            ->filter(fn ($v) => $v !== '')
            ->values()
            ->all();
    }

    /** Limpia, quita vacíos y deduplica sin distinguir mayúsculas ni acentos. */
    private function unicos(array $valores): array
    {
        $acentos = ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n'];
        $resultado = [];
        $vistos = [];
        foreach ($valores as $valor) {
            $valor = trim((string) $valor);
            if ($valor === '') {
                continue;
            }
            $llave = strtr(mb_strtolower($valor), $acentos);
            if (isset($vistos[$llave])) {
                continue;
            }
            $vistos[$llave] = true;
            $resultado[] = $valor;
        }
        return $resultado;
    }
};
