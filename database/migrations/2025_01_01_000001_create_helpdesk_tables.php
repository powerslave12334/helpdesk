<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. tblDepto
        Schema::create('tblDepto', function (Blueprint $table) {
            $table->id('id_Depto');
            $table->string('nombre', 100);
            $table->dateTime('fechaAlta');
            $table->boolean('activo');
        });

        // 2. tblArea
        Schema::create('tblArea', function (Blueprint $table) {
            $table->id('id_Area');
            $table->string('nombre', 100);
            $table->dateTime('fechaAlta');
            $table->boolean('activo');
        });

        // 3. tblTipoEmpleado
        Schema::create('tblTipoEmpleado', function (Blueprint $table) {
            $table->id('id_TipoEmpleado');
            $table->string('nombre', 50);
            $table->dateTime('fechaAlta');
            $table->boolean('activo');
        });

        // 4. tblOficinas
        Schema::create('tblOficinas', function (Blueprint $table) {
            $table->id('id_Oficinas');
            $table->string('nombre', 5);
            $table->string('descripcion', 250)->nullable();
            $table->dateTime('fechaAlta');
            $table->boolean('activo');
        });

        // 5. tblModulos
        Schema::create('tblModulos', function (Blueprint $table) {
            $table->id('id_Modulos');
            $table->string('nombre', 50);
            $table->dateTime('fechaAlta');
            $table->boolean('activo');
            $table->integer('orden')->nullable();
        });

        // 7. tblTipoEquipo
        Schema::create('tblTipoEquipo', function (Blueprint $table) {
            $table->id('id_TipoEquipo');
            $table->string('descripcion', 250);
            $table->dateTime('fechaAlta');
            $table->boolean('activo');
        });

        // 8. tblTipoDesarrollo
        Schema::create('tblTipoDesarrollo', function (Blueprint $table) {
            $table->id('id_TipoDesarrollo');
            $table->string('nombre', 50);
            $table->boolean('enabled');
            $table->text('descripcion')->nullable();
        });

        // 9. tblSistemas
        Schema::create('tblSistemas', function (Blueprint $table) {
            $table->id('id_sistemas');
            $table->string('nombre', 50);
            $table->boolean('enabled');
        });

        // 10. tblSubModulos (FK: tblModulos)
        Schema::create('tblSubModulos', function (Blueprint $table) {
            $table->id('id_SubModulos');
            $table->foreignId('id_Modulos')->constrained('tblModulos', 'id_Modulos');
            $table->string('nombre', 50);
            $table->string('controller', 100)->nullable();
            $table->string('action', 50)->nullable();
            $table->dateTime('fechaAlta');
            $table->boolean('activo');
            $table->integer('orden')->nullable();
        });

        // 11. tblEstatus (FK: tblModulos)
        Schema::create('tblEstatus', function (Blueprint $table) {
            $table->id('id_Estatus');
            $table->string('descripcion', 100);
            $table->dateTime('fechaAlta');
            $table->boolean('activo');
            $table->foreignId('id_Modulos')->nullable()->constrained('tblModulos', 'id_Modulos');
        });

        // 12. tblTipoSoporte (FK: tblArea)
        Schema::create('tblTipoSoporte', function (Blueprint $table) {
            $table->id('id_TipoSoporte');
            $table->string('descripcion', 100);
            $table->dateTime('fechaAlta');
            $table->boolean('activo');
            $table->foreignId('id_Area')->nullable()->constrained('tblArea', 'id_Area');
        });

        // 14. tblRoles
        Schema::create('tblRoles', function (Blueprint $table) {
            $table->id('id_Rol');
            $table->string('nombre', 50);
            $table->string('descipcion', 50)->nullable();
            $table->dateTime('fechaAlta');
            $table->boolean('activo');
        });

        // 15. tblUsuario (FK: tblDepto, tblArea, tblTipoEmpleado, tblOficinas)
        Schema::create('tblUsuario', function (Blueprint $table) {
            $table->id('id_Usuario');
            $table->string('nombre', 100);
            $table->string('app', 50);
            $table->string('apm', 50);
            $table->string('correo', 100);
            $table->string('telefono', 15)->nullable();
            $table->foreignId('id_Depto')->constrained('tblDepto', 'id_Depto');
            $table->foreignId('id_Area')->constrained('tblArea', 'id_Area');
            $table->boolean('esTecnico');
            $table->foreignId('id_TipoEmpleado')->constrained('tblTipoEmpleado', 'id_TipoEmpleado');
            $table->dateTime('fechaAlta');
            $table->boolean('activo');
            $table->foreignId('id_Oficinas')->nullable()->constrained('tblOficinas', 'id_Oficinas');
        });

        // 16. tblLogin (FK: tblUsuario)
        Schema::create('tblLogin', function (Blueprint $table) {
            $table->id('id_Login');
            $table->foreignId('id_Usuario')->constrained('tblUsuario', 'id_Usuario');
            $table->string('usuario', 50);
            $table->string('pass', 256);
            $table->dateTime('fechaAlta');
            $table->boolean('activo');
        });

        // 17. tblRolesUsuarios (FK: tblRoles, tblUsuario)
        Schema::create('tblRolesUsuarios', function (Blueprint $table) {
            $table->id('id_RolesUsuario');
            $table->foreignId('id_Rol')->constrained('tblRoles', 'id_Rol');
            $table->foreignId('id_Usuario')->constrained('tblUsuario', 'id_Usuario');
            $table->dateTime('fechaAlta');
            $table->boolean('activo');
        });

        // 20. tblEquipo (FK: tblTipoEquipo, tblEstatus, tblOficinas)
        Schema::create('tblEquipo', function (Blueprint $table) {
            $table->id('id_Equipo');
            $table->string('marca', 150);
            $table->string('modelo', 150);
            $table->foreignId('id_TipoEquipo')->constrained('tblTipoEquipo', 'id_TipoEquipo');
            $table->foreignId('id_Estatus')->constrained('tblEstatus', 'id_Estatus');
            $table->dateTime('fechaAdquisicion')->nullable();
            $table->string('numeroSerie', 150)->nullable();
            $table->string('nombreEquipo', 150)->nullable();
            $table->string('ubicacion', 150)->nullable();
            $table->text('observaciones')->nullable();
            $table->integer('id_PeriodoMantenimiento');
            $table->dateTime('fechaAlta');
            $table->boolean('activo');
            $table->foreignId('id_Oficinas')->nullable()->constrained('tblOficinas', 'id_Oficinas');
            $table->boolean('esCompartido');
        });

        // 22. tblEquipoUsuario (FK: tblEquipo, tblUsuario, tblArea)
        Schema::create('tblEquipoUsuario', function (Blueprint $table) {
            $table->id('id_EquipoUsuario');
            $table->foreignId('id_Equipo')->constrained('tblEquipo', 'id_Equipo');
            $table->foreignId('id_Usuario')->constrained('tblUsuario', 'id_Usuario');
            $table->dateTime('fechaAsignacion');
            $table->dateTime('fechaBaja')->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('id_Area')->nullable()->constrained('tblArea', 'id_Area');
            $table->dateTime('fechaAlta');
            $table->boolean('activo');
        });

        // 23. tblHistorialEquipoUsuario (FK: tblEquipo, tblUsuario)
        Schema::create('tblHistorialEquipoUsuario', function (Blueprint $table) {
            $table->id('id_HistorialEquipoUsuario');
            $table->integer('id_EquipoUsuarioOriginal');
            $table->foreignId('id_Equipo')->constrained('tblEquipo', 'id_Equipo');
            $table->foreignId('id_Usuario')->constrained('tblUsuario', 'id_Usuario');
            $table->dateTime('fechaAsignacion');
            $table->dateTime('fechaBaja')->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('id_Area')->nullable()->constrained('tblArea', 'id_Area');
            $table->dateTime('fechaAlta');
            $table->dateTime('fechaAccion');
            $table->integer('id_UsuarioAccion');
            $table->string('accion', 100);
            $table->string('motivo', 500)->nullable();
            $table->string('equipoMarca', 150)->nullable();
            $table->string('equipoModelo', 150)->nullable();
            $table->string('equipoNombre', 150)->nullable();
            $table->string('equipoNumeroSerie', 150)->nullable();
            $table->string('equipoUbicacion', 150)->nullable();
        });

        // 24. tblTicket (FK: tblUsuario, tblTipoSoporte, tblEstatus, tblEquipo)
        Schema::create('tblTicket', function (Blueprint $table) {
            $table->id('id_Ticket');
            $table->foreignId('id_Usuario')->constrained('tblUsuario', 'id_Usuario');
            $table->foreignId('id_TipoSoporte')->constrained('tblTipoSoporte', 'id_TipoSoporte');
            $table->foreignId('id_Tecnico')->nullable()->constrained('tblUsuario', 'id_Usuario');
            $table->string('asunto', 200);
            $table->text('problema');
            $table->string('img', 150)->nullable();
            $table->dateTime('fechaAlta');
            $table->boolean('activo');
            $table->foreignId('id_Estatus')->nullable()->constrained('tblEstatus', 'id_Estatus');
            $table->foreignId('id_Equipo')->nullable()->constrained('tblEquipo', 'id_Equipo');
            $table->dateTime('fechaCierre')->nullable();
        });

        // 25. tblTicketComentarios (FK: tblTicket, tblUsuario, tblEstatus)
        Schema::create('tblTicketComentarios', function (Blueprint $table) {
            $table->id('id_TicketComentario');
            $table->foreignId('id_Ticket')->constrained('tblTicket', 'id_Ticket');
            $table->text('comentario');
            $table->dateTime('fechaComentario');
            $table->foreignId('id_Usuario')->constrained('tblUsuario', 'id_Usuario');
            $table->foreignId('id_Estatus')->nullable()->constrained('tblEstatus', 'id_Estatus');
            $table->dateTime('fechaAlta');
            $table->boolean('activo');
        });

        // 26. tblTicketEvidencia (FK: tblTicket, tblUsuario)
        Schema::create('tblTicketEvidencia', function (Blueprint $table) {
            $table->id('id_TicketEvidencia');
            $table->foreignId('id_Ticket')->constrained('tblTicket', 'id_Ticket');
            $table->string('nombreArchivo', 255);
            $table->string('rutaArchivo', 500);
            $table->string('extension', 20)->nullable();
            $table->string('tipoContenido', 100)->nullable();
            $table->bigInteger('tamanioBytes')->nullable();
            $table->foreignId('id_UsuarioAlta')->constrained('tblUsuario', 'id_Usuario');
            $table->dateTime('fechaAlta');
            $table->boolean('activo');
        });

        // 27. tblTicketArchivo (FK: tblTicket, tblUsuario)
        Schema::create('tblTicketArchivo', function (Blueprint $table) {
            $table->id('id_TicketArchivo');
            $table->foreignId('id_Ticket')->constrained('tblTicket', 'id_Ticket');
            $table->foreignId('id_Usuario')->constrained('tblUsuario', 'id_Usuario');
            $table->string('tipoArchivo', 50);
            $table->string('ruta', 250);
            $table->string('nombreOriginal', 250)->nullable();
            $table->string('extension', 20)->nullable();
            $table->dateTime('fechaAlta');
            $table->boolean('activo');
        });

        // 28. tblTicketDesarrolloDetalle (FK: tblTicket, tblTipoDesarrollo, tblSistemas)
        Schema::create('tblTicketDesarrolloDetalle', function (Blueprint $table) {
            $table->id('id_TicketDesarrolloDetalle');
            $table->foreignId('id_Ticket')->nullable()->constrained('tblTicket', 'id_Ticket');
            $table->foreignId('id_TipoDesarrollo')->nullable()->constrained('tblTipoDesarrollo', 'id_TipoDesarrollo');
            $table->foreignId('id_Sistemas')->nullable()->constrained('tblSistemas', 'id_sistemas');
            $table->integer('id_Modulo')->nullable();
            $table->integer('id_Submodulo')->nullable();
            $table->boolean('aprobado')->nullable();
            $table->integer('id_UsuarioAprobo')->nullable();
            $table->dateTime('fechaAprobo')->nullable();
            $table->dateTime('fechaEstimadaTermino')->nullable();
        });

        // 29. tblNotificaciones (FK: tblUsuario)
        Schema::create('tblNotificaciones', function (Blueprint $table) {
            $table->id('id_Notificacion');
            $table->foreignId('id_UsuarioDestino')->constrained('tblUsuario', 'id_Usuario');
            $table->string('Mensaje', 500);
            $table->string('Tipo', 50);
            $table->string('UrlAccion', 200)->nullable();
            $table->boolean('Leido')->nullable();
            $table->dateTime('FechaCreacion')->nullable();
        });

        // 30. tblConfiguracionSistema
        Schema::create('tblConfiguracionSistema', function (Blueprint $table) {
            $table->id('id_ConfiguracionSistema');
            $table->string('clave', 100);
            $table->text('valor');
            $table->string('descripcion', 250)->nullable();
            $table->dateTime('fechaAlta');
            $table->boolean('activo');
        });
    }

    public function down(): void
    {
        $tables = [
            'tblConfiguracionSistema', 'tblNotificaciones', 'tblTicketDesarrolloDetalle',
            'tblTicketArchivo', 'tblTicketEvidencia', 'tblTicketComentarios', 'tblTicket',
            'tblHistorialEquipoUsuario', 'tblEquipoUsuario', 'tblEquipo',
            'tblRolesUsuarios', 'tblLogin', 'tblUsuario',
            'tblTipoSoporte', 'tblEstatus', 'tblSubModulos', 'tblTipoDesarrollo',
            'tblSistemas', 'tblTipoEquipo', 'tblRoles', 'tblModulos',
            'tblOficinas', 'tblTipoEmpleado', 'tblArea', 'tblDepto',
        ];
        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
    }
};
