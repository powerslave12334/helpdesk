<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Los tickets del área Desarrollo de software se crearon con el tipo por
     * defecto ('falla_tecnica'). El KPI Desarrollo y el listado necesitan
     * distinguirlos con su propio tipo: 'desarrollo'.
     */
    public function up(): void
    {
        $idDesarrollo = DB::table('tblTipoSoporte')
            ->where('descripcion', 'Desarrollo de software')
            ->value('id_TipoSoporte');

        if (!$idDesarrollo) {
            return;
        }

        DB::table('tblTicket')
            ->where('id_TipoSoporte', $idDesarrollo)
            ->where('tipoTicket', 'falla_tecnica')
            ->update(['tipoTicket' => 'desarrollo']);
    }

    public function down(): void
    {
        $idDesarrollo = DB::table('tblTipoSoporte')
            ->where('descripcion', 'Desarrollo de software')
            ->value('id_TipoSoporte');

        if (!$idDesarrollo) {
            return;
        }

        DB::table('tblTicket')
            ->where('id_TipoSoporte', $idDesarrollo)
            ->where('tipoTicket', 'desarrollo')
            ->update(['tipoTicket' => 'falla_tecnica']);
    }
};
