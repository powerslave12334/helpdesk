<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportarFaltantes extends Command
{
    protected $signature = 'db:importar-faltantes';
    protected $description = 'Sincroniza registros de productivo a local (importa faltantes y actualiza tickets existentes)';

    private array $camposTicket = ['id_Estatus', 'id_Tecnico', 'fechaCierre', 'activo', 'id_Equipo', 'id_TipoSoporte'];

    public function handle()
    {
        $this->line('=== SINCRONIZACIÓN PRODUCTIVO → LOCAL ===');
        $this->newLine();

        try {
            DB::connection('sqlsrv_productivo')->getPdo();
            $this->info('Conectado a productivo');
        } catch (\Exception $e) {
            $this->error('Error conectando a productivo: ' . $e->getMessage());
            return 1;
        }

        $this->importarEquiposUsuarios();
        $this->sincronizarTicketsExistentes();
        $this->importarTickets();
        $this->limpiarComentariosAutomaticos();
        $this->importarComentarios();
        $this->importarEvidencias();
        $this->verificarEstatus();
        $this->verificarHistorial();

        $this->newLine();
        $this->info('=== SINCRONIZACIÓN COMPLETADA ===');
        return 0;
    }

    private function normalizar($valor)
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if (is_string($valor) && preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}/', $valor)) {
            return date('Y-m-d H:i:s', strtotime($valor));
        }

        return (string) $valor;
    }

    private function importarEquiposUsuarios()
    {
        $this->line('--- tblequipousuario ---');

        $idsProductivo = DB::connection('sqlsrv_productivo')
            ->select('SELECT id_EquipoUsuario FROM tblEquipoUsuario ORDER BY id_EquipoUsuario');
        $idsProductivo = array_map(fn($r) => $r->id_EquipoUsuario, $idsProductivo);

        $idsLocal = DB::connection('mysql')
            ->select('SELECT id_EquipoUsuario FROM tblequipousuario ORDER BY id_EquipoUsuario');
        $idsLocal = array_map(fn($r) => $r->id_EquipoUsuario, $idsLocal);

        $faltantes = array_diff($idsProductivo, $idsLocal);

        if (empty($faltantes)) {
            $this->info('✅ Todos los registros están presentes');
            return;
        }

        $this->info("Faltan " . count($faltantes) . " registros: " . implode(', ', $faltantes));

        foreach ($faltantes as $id) {
            $registro = DB::connection('sqlsrv_productivo')
                ->selectOne('SELECT * FROM tblEquipoUsuario WHERE id_EquipoUsuario = ?', [$id]);

            if (!$registro) continue;

            DB::connection('mysql')->table('tblequipousuario')->insert([
                'id_EquipoUsuario' => $registro->id_EquipoUsuario,
                'id_Equipo' => $registro->id_Equipo,
                'id_Usuario' => $registro->id_Usuario,
                'fechaAsignacion' => $registro->fechaAsignacion,
                'fechaBaja' => $registro->fechaBaja,
                'observaciones' => $registro->observaciones,
                'id_Area' => $registro->id_Area,
                'fechaAlta' => $registro->fechaAlta,
                'activo' => $registro->activo,
            ]);

            $this->line("   ✅ Importado ID $id");
        }
    }

    private function sincronizarTicketsExistentes()
    {
        $this->line('--- tblticket (actualizar existentes) ---');

        $productivos = DB::connection('sqlsrv_productivo')->table('tblTicket')->get();
        $actualizados = 0;

        foreach ($productivos as $p) {
            $local = DB::connection('mysql')->table('tblticket')->where('id_Ticket', $p->id_Ticket)->first();

            if (!$local) {
                continue;
            }

            $cambios = [];
            foreach ($this->camposTicket as $campo) {
                $valorProd = $this->normalizar($p->$campo ?? null);
                $valorLocal = $this->normalizar($local->$campo ?? null);

                if ($valorProd !== $valorLocal) {
                    $cambios[$campo] = $valorProd;
                }
            }

            if (!empty($cambios)) {
                DB::connection('mysql')->table('tblticket')
                    ->where('id_Ticket', $p->id_Ticket)
                    ->update($cambios);
                $actualizados++;
                $this->line("   ↻ Ticket #{$p->id_Ticket}: " . implode(', ', array_keys($cambios)));
            }
        }

        $this->info("Tickets actualizados: {$actualizados}");
    }

    private function importarTickets()
    {
        $this->line('--- tblticket (importar faltantes) ---');

        $idsProductivo = DB::connection('sqlsrv_productivo')
            ->select('SELECT id_Ticket FROM tblTicket ORDER BY id_Ticket');
        $idsProductivo = array_map(fn($r) => $r->id_Ticket, $idsProductivo);

        $idsLocal = DB::connection('mysql')
            ->select('SELECT id_Ticket FROM tblticket ORDER BY id_Ticket');
        $idsLocal = array_map(fn($r) => $r->id_Ticket, $idsLocal);

        $faltantes = array_diff($idsProductivo, $idsLocal);

        if (empty($faltantes)) {
            $this->info('✅ Todos los registros están presentes');
            return;
        }

        $this->info("Faltan " . count($faltantes) . " registros: " . implode(', ', $faltantes));

        foreach ($faltantes as $id) {
            $registro = DB::connection('sqlsrv_productivo')
                ->selectOne('SELECT * FROM tblTicket WHERE id_Ticket = ?', [$id]);

            if (!$registro) continue;

            DB::connection('mysql')->table('tblticket')->insert([
                'id_Ticket' => $registro->id_Ticket,
                'id_Usuario' => $registro->id_Usuario,
                'id_TipoSoporte' => $registro->id_TipoSoporte,
                'id_Tecnico' => $registro->id_Tecnico,
                'asunto' => $registro->asunto,
                'problema' => $registro->problema,
                'img' => $registro->img,
                'fechaAlta' => $registro->fechaAlta,
                'activo' => $registro->activo,
                'id_Estatus' => $registro->id_Estatus,
                'id_Equipo' => $registro->id_Equipo,
                'fechaCierre' => $this->normalizar($registro->fechaCierre),
            ]);

            $this->line("   ✅ Importado ID $id");
        }
    }

    private function limpiarComentariosAutomaticos()
    {
        $this->line('--- tblticketcomentarios (limpiar automáticos) ---');

        $ids = DB::connection('mysql')->table('tblticketcomentarios')
            ->where('comentario', 'like', '%cerrado automáticamente por inactividad%')
            ->pluck('id_TicketComentario')
            ->all();

        if (!empty($ids)) {
            DB::connection('mysql')->table('tblticketcomentarios')
                ->whereIn('id_TicketComentario', $ids)
                ->delete();
        }

        $this->info('Comentarios automáticos eliminados: ' . count($ids));
    }

    private function importarComentarios()
    {
        $this->line('--- tblticketcomentarios ---');

        $idsProductivo = DB::connection('sqlsrv_productivo')
            ->select('SELECT id_TicketComentario FROM tblTicketComentarios ORDER BY id_TicketComentario');
        $idsProductivo = array_map(fn($r) => $r->id_TicketComentario, $idsProductivo);

        $idsLocal = DB::connection('mysql')
            ->select('SELECT id_TicketComentario FROM tblticketcomentarios ORDER BY id_TicketComentario');
        $idsLocal = array_map(fn($r) => $r->id_TicketComentario, $idsLocal);

        $faltantes = array_diff($idsProductivo, $idsLocal);

        if (empty($faltantes)) {
            $this->info('✅ Todos los registros están presentes');
            return;
        }

        $this->info("Faltan " . count($faltantes) . " registros: " . implode(', ', $faltantes));

        foreach ($faltantes as $id) {
            $registro = DB::connection('sqlsrv_productivo')
                ->selectOne('SELECT * FROM tblTicketComentarios WHERE id_TicketComentario = ?', [$id]);

            if (!$registro) continue;

            DB::connection('mysql')->table('tblticketcomentarios')->insert([
                'id_TicketComentario' => $registro->id_TicketComentario,
                'id_Ticket' => $registro->id_Ticket,
                'comentario' => $registro->comentario,
                'fechaComentario' => $registro->fechaComentario,
                'id_Usuario' => $registro->id_Usuario,
                'id_Estatus' => $registro->id_Estatus,
                'fechaAlta' => $registro->fechaAlta,
                'activo' => $registro->activo,
            ]);

            $this->line("   ✅ Importado ID $id");
        }
    }

    private function importarEvidencias()
    {
        $this->line('--- tblticketevidencia ---');

        $idsProductivo = DB::connection('sqlsrv_productivo')
            ->select('SELECT id_TicketEvidencia FROM tblTicketEvidencia ORDER BY id_TicketEvidencia');
        $idsProductivo = array_map(fn($r) => $r->id_TicketEvidencia, $idsProductivo);

        $idsLocal = DB::connection('mysql')
            ->select('SELECT id_TicketEvidencia FROM tblticketevidencia ORDER BY id_TicketEvidencia');
        $idsLocal = array_map(fn($r) => $r->id_TicketEvidencia, $idsLocal);

        $faltantes = array_diff($idsProductivo, $idsLocal);

        if (empty($faltantes)) {
            $this->info('✅ Todos los registros están presentes');
            return;
        }

        $this->info("Faltan " . count($faltantes) . " registros: " . implode(', ', $faltantes));

        foreach ($faltantes as $id) {
            $registro = DB::connection('sqlsrv_productivo')
                ->selectOne('SELECT * FROM tblTicketEvidencia WHERE id_TicketEvidencia = ?', [$id]);

            if (!$registro) continue;

            DB::connection('mysql')->table('tblticketevidencia')->insert([
                'id_TicketEvidencia' => $registro->id_TicketEvidencia,
                'id_Ticket' => $registro->id_Ticket,
                'nombreArchivo' => $registro->nombreArchivo,
                'rutaArchivo' => $registro->rutaArchivo,
                'extension' => $registro->extension,
                'tipoContenido' => $registro->tipoContenido,
                'tamanioBytes' => $registro->tamanioBytes,
                'id_UsuarioAlta' => $registro->id_UsuarioAlta,
                'fechaAlta' => $registro->fechaAlta,
                'activo' => $registro->activo,
            ]);

            $this->line("   ✅ Importado ID $id");
        }
    }

    private function verificarEstatus()
    {
        $this->line('--- tblstatus (verificación) ---');

        $idsProductivo = DB::connection('sqlsrv_productivo')
            ->select('SELECT id_Estatus FROM tblEstatus ORDER BY id_Estatus');
        $idsProductivo = array_map(fn($r) => $r->id_Estatus, $idsProductivo);

        $idsLocal = DB::connection('mysql')
            ->select('SELECT id_Estatus FROM tblestatus ORDER BY id_Estatus');
        $idsLocal = array_map(fn($r) => $r->id_Estatus, $idsLocal);

        $sobrantes = array_diff($idsLocal, $idsProductivo);

        if (empty($sobrantes)) {
            $this->info('✅ No hay registros sobrantes');
            return;
        }

        $this->warn("Registros en local que no están en productivo: " . implode(', ', $sobrantes));

        foreach ($sobrantes as $id) {
            $registro = DB::connection('mysql')
                ->selectOne('SELECT * FROM tblestatus WHERE id_Estatus = ?', [$id]);
            $this->line("   ID $id: " . ($registro->descripcion ?? 'N/A'));
        }
    }

    private function verificarHistorial()
    {
        $this->line('--- tblhistorialequipousuario (verificación) ---');

        $idsProductivo = DB::connection('sqlsrv_productivo')
            ->select('SELECT id_HistorialEquipoUsuario FROM tblHistorialEquipoUsuario ORDER BY id_HistorialEquipoUsuario');
        $idsProductivo = array_map(fn($r) => $r->id_HistorialEquipoUsuario, $idsProductivo);

        $idsLocal = DB::connection('mysql')
            ->select('SELECT id_HistorialEquipoUsuario FROM tblhistorialequipousuario ORDER BY id_HistorialEquipoUsuario');
        $idsLocal = array_map(fn($r) => $r->id_HistorialEquipoUsuario, $idsLocal);

        $sobrantes = array_diff($idsLocal, $idsProductivo);

        if (empty($sobrantes)) {
            $this->info('✅ No hay registros sobrantes');
            return;
        }

        $this->warn("Registros en local que no están en productivo: " . implode(', ', $sobrantes));
    }
}
