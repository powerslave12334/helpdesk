<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $claves = ['modulos', 'roles-usuarios'];

    public function up(): void
    {
        $ids = DB::table('tblSubModulos')->whereIn('clave', $this->claves)->pluck('id_SubModulos')->all();

        if (!empty($ids)) {
            DB::table('tblRolSubModulo')->whereIn('id_SubModulos', $ids)->delete();
        }

        DB::table('tblSubModulos')->whereIn('clave', $this->claves)->delete();
    }

    public function down(): void
    {
        // Limpieza de datos no reversible.
    }
};
