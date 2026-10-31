# HELPDESK — Esquema Completo de Base de Datos

> **Total:** 38 tablas | **Migraciones Laravel existentes:** 33 tablas | **Sin migración:** 5 tablas
> **Convenciones:** PKs auto-increment bigint | Sin timestamps Laravel (usan `fechaAlta`) | Soft deletes manuales (`boolean activo`)

---

## Convenciones del Proyecto

| Convención | Detalle |
|------------|---------|
| **PK** | `id_{NombreTabla}` — bigint auto-increment |
| **FK** | `id_{TablaReferenciada}` — bigint, nombre exacto de la PK referenciada |
| **Soft delete** | Columna `activo` (boolean) en casi todas las tablas |
| **Timestamps** | Columna `fechaAlta` (datetime) — NO se usa `created_at`/`updated_at` |
| **Auth driver** | Provider personalizado `helpdesk` — `HelpdeskUserProvider` |
| **Password hash** | `SHA-512` sin salt (ver `HelpdeskUserProvider.php:60`) |
| **User dual** | `User` (Auth) y `Usuario` (datos) apuntan a la misma tabla `tblUsuario` |

---

## Módulo 1: Organización

### tblDepto — Departamentos

```sql
CREATE TABLE tblDepto (
    id_Depto BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    fechaAlta DATETIME NOT NULL,
    activo TINYINT(1) NOT NULL,
    PRIMARY KEY (id_Depto)
);
```

| Columna | Tipo | Nullable | Default | FK |
|---------|------|----------|---------|-----|
| id_Depto | bigint PK | No | auto_increment | — |
| nombre | varchar(100) | No | — | — |
| fechaAlta | datetime | No | — | — |
| activo | boolean | No | — | — |

---

### tblArea — Áreas

```sql
CREATE TABLE tblArea (
    id_Area BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    fechaAlta DATETIME NOT NULL,
    activo TINYINT(1) NOT NULL,
    PRIMARY KEY (id_Area)
);
```

| Columna | Tipo | Nullable | Default | FK |
|---------|------|----------|---------|-----|
| id_Area | bigint PK | No | auto_increment | — |
| nombre | varchar(100) | No | — | — |
| fechaAlta | datetime | No | — | — |
| activo | boolean | No | — | — |

---

### tblTipoEmpleado — Tipo de Empleado

```sql
CREATE TABLE tblTipoEmpleado (
    id_TipoEmpleado BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL,
    fechaAlta DATETIME NOT NULL,
    activo TINYINT(1) NOT NULL,
    PRIMARY KEY (id_TipoEmpleado)
);
```

| Columna | Tipo | Nullable | Default | FK |
|---------|------|----------|---------|-----|
| id_TipoEmpleado | bigint PK | No | auto_increment | — |
| nombre | varchar(50) | No | — | — |
| fechaAlta | datetime | No | — | — |
| activo | boolean | No | — | — |

---

### tblOficinas — Oficinas

```sql
CREATE TABLE tblOficinas (
    id_Oficinas BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(5) NOT NULL,
    descripcion VARCHAR(250) NULL,
    fechaAlta DATETIME NOT NULL,
    activo TINYINT(1) NOT NULL,
    PRIMARY KEY (id_Oficinas)
);
```

| Columna | Tipo | Nullable | Default | FK |
|---------|------|----------|---------|-----|
| id_Oficinas | bigint PK | No | auto_increment | — |
| nombre | varchar(5) | No | — | — |
| descripcion | varchar(250) | Si | — | — |
| fechaAlta | datetime | No | — | — |
| activo | boolean | No | — | — |

---

### tblUsuario — Usuarios / Empleados

```sql
CREATE TABLE tblUsuario (
    id_Usuario BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    app VARCHAR(50) NOT NULL,
    apm VARCHAR(50) NOT NULL,
    correo VARCHAR(100) NOT NULL,
    telefono VARCHAR(15) NULL,
    id_Depto BIGINT UNSIGNED NOT NULL,
    id_Area BIGINT UNSIGNED NOT NULL,
    esTecnico TINYINT(1) NOT NULL,
    id_TipoEmpleado BIGINT UNSIGNED NOT NULL,
    fechaAlta DATETIME NOT NULL,
    activo TINYINT(1) NOT NULL,
    id_Oficinas BIGINT UNSIGNED NULL,
    PRIMARY KEY (id_Usuario),
    FOREIGN KEY (id_Depto) REFERENCES tblDepto(id_Depto),
    FOREIGN KEY (id_Area) REFERENCES tblArea(id_Area),
    FOREIGN KEY (id_TipoEmpleado) REFERENCES tblTipoEmpleado(id_TipoEmpleado),
    FOREIGN KEY (id_Oficinas) REFERENCES tblOficinas(id_Oficinas)
);
```

| Columna | Tipo | Nullable | Default | FK |
|---------|------|----------|---------|-----|
| id_Usuario | bigint PK | No | auto_increment | — |
| nombre | varchar(100) | No | — | — |
| app | varchar(50) | No | — | — |
| apm | varchar(50) | No | — | — |
| correo | varchar(100) | No | — | — |
| telefono | varchar(15) | Si | — | — |
| id_Depto | bigint FK | No | — | → tblDepto.id_Depto |
| id_Area | bigint FK | No | — | → tblArea.id_Area |
| esTecnico | boolean | No | — | — |
| id_TipoEmpleado | bigint FK | No | — | → tblTipoEmpleado.id_TipoEmpleado |
| fechaAlta | datetime | No | — | — |
| activo | boolean | No | — | — |
| id_Oficinas | bigint FK | Si | — | → tblOficinas.id_Oficinas |

**Relaciones Eloquent:**
- `belongsTo` → Departamento (id_Depto)
- `belongsTo` → Area (id_Area)
- `belongsTo` → TipoEmpleado (id_TipoEmpleado)
- `belongsTo` → Oficina (id_Oficinas)
- `hasOne` → Login (id_Usuario)
- `belongsToMany` → Rol (via tblRolesUsuarios)
- `hasMany` → Ticket (como creador, id_Usuario)
- `hasMany` → Ticket (como técnico, id_Tecnico)
- `belongsToMany` → Equipo (via tblEquipoUsuario)

---

### tblLogin — Credenciales de Acceso

```sql
CREATE TABLE tblLogin (
    id_Login BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_Usuario BIGINT UNSIGNED NOT NULL,
    usuario VARCHAR(50) NOT NULL,
    pass VARCHAR(256) NOT NULL,
    fechaAlta DATETIME NOT NULL,
    activo TINYINT(1) NOT NULL,
    PRIMARY KEY (id_Login),
    FOREIGN KEY (id_Usuario) REFERENCES tblUsuario(id_Usuario)
);
```

| Columna | Tipo | Nullable | Default | FK |
|---------|------|----------|---------|-----|
| id_Login | bigint PK | No | auto_increment | — |
| id_Usuario | bigint FK | No | — | → tblUsuario.id_Usuario |
| usuario | varchar(50) | No | — | — |
| pass | varchar(256) | No | — | SHA-512 hash |
| fechaAlta | datetime | No | — | — |
| activo | boolean | No | — | — |

**Nota:** Password hasheado con `hash('sha512', $password)` — ver `HelpdeskUserProvider.php:60`

---

## Módulo 2: Roles y Permisos

### tblRoles — Roles

```sql
CREATE TABLE tblRoles (
    id_Rol BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL,
    descipcion VARCHAR(50) NULL,
    fechaAlta DATETIME NOT NULL,
    activo TINYINT(1) NOT NULL,
    PRIMARY KEY (id_Rol)
);
```

| Columna | Tipo | Nullable | Default | FK |
|---------|------|----------|---------|-----|
| id_Rol | bigint PK | No | auto_increment | — |
| nombre | varchar(50) | No | — | — |
| descipcion | varchar(50) | Si | — | — (typo: falta "r") |
| fechaAlta | datetime | No | — | — |
| activo | boolean | No | — | — |

**Nota:** Se detecta admin por `LOWER(nombre) LIKE '%admin%'` o `LOWER(nombre) = 'root'` — ver `PermisosService.php:13-25`

---

### tblRolesUsuarios — Asignación Rol-Usuario (Pivote)

```sql
CREATE TABLE tblRolesUsuarios (
    id_RolesUsuario BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_Rol BIGINT UNSIGNED NOT NULL,
    id_Usuario BIGINT UNSIGNED NOT NULL,
    fechaAlta DATETIME NOT NULL,
    activo TINYINT(1) NOT NULL,
    PRIMARY KEY (id_RolesUsuario),
    FOREIGN KEY (id_Rol) REFERENCES tblRoles(id_Rol),
    FOREIGN KEY (id_Usuario) REFERENCES tblUsuario(id_Usuario)
);
```

| Columna | Tipo | Nullable | Default | FK |
|---------|------|----------|---------|-----|
| id_RolesUsuario | bigint PK | No | auto_increment | — |
| id_Rol | bigint FK | No | — | → tblRoles.id_Rol |
| id_Usuario | bigint FK | No | — | → tblUsuario.id_Usuario |
| fechaAlta | datetime | No | — | — |
| activo | boolean | No | — | — |

---

### tblModulos — Módulos del Sistema

```sql
CREATE TABLE tblModulos (
    id_Modulos BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL,
    fechaAlta DATETIME NOT NULL,
    activo TINYINT(1) NOT NULL,
    orden INT NULL,
    PRIMARY KEY (id_Modulos)
);
```

| Columna | Tipo | Nullable | Default | FK |
|---------|------|----------|---------|-----|
| id_Modulos | bigint PK | No | auto_increment | — |
| nombre | varchar(50) | No | — | — |
| fechaAlta | datetime | No | — | — |
| activo | boolean | No | — | — |
| orden | int | Si | — | — |

> **Fuente de verdad:** `config/modulos.php`. Esta tabla es un espejo que se
> actualiza con `php artisan modulos:sync` (la migración `2026_10_02_000002` lo
> ejecuta en deploy). No existe formulario para crear/editar módulos: se
> agregan en el archivo de configuración, en sus rutas (`permiso:<clave>`) y en
> el sidebar, que se genera desde ese mismo archivo.

---

### tblSubModulos — Submódulos

```sql
CREATE TABLE tblSubModulos (
    id_SubModulos BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_Modulos BIGINT UNSIGNED NOT NULL,
    nombre VARCHAR(50) NOT NULL,
    controller VARCHAR(100) NULL,
    clave VARCHAR(100) NULL UNIQUE,
    action VARCHAR(50) NULL,
    fechaAlta DATETIME NOT NULL,
    activo TINYINT(1) NOT NULL,
    orden INT NULL,
    PRIMARY KEY (id_SubModulos),
    FOREIGN KEY (id_Modulos) REFERENCES tblModulos(id_Modulos)
);
```

| Columna | Tipo | Nullable | Default | FK |
|---------|------|----------|---------|-----|
| id_SubModulos | bigint PK | No | auto_increment | — |
| id_Modulos | bigint FK | No | — | → tblModulos.id_Modulos |
| nombre | varchar(50) | No | — | — |
| controller | varchar(100) | Si | — | — |
| clave | varchar(100) | Si | — | única; usada en rutas y sidebar (ej. `tickets`, `tipo-soporte`, `mantenimientos`) |
| action | varchar(50) | Si | — | — |
| fechaAlta | datetime | No | — | — |
| activo | boolean | No | — | — |
| orden | int | Si | — | — |

> **Fuente de verdad:** `config/modulos.php` (sincronizado con
> `php artisan modulos:sync`; ver nota en `tblModulos`). `clave` identifica al
> submódulo en el menú y en el middleware `permiso:<clave>`; la pantalla que
> permitía crear/editar submódulos fue eliminada. Las columnas `controller` y
> `action` son legacy: ya no las lee ninguna parte del sistema.

---

### tblAcciones — ELIMINADA

> Eliminada por la migración `2026_09_28_000004_limpieza_modulos_y_tablas_legacy.php`. Ya no participa en permisos (la matriz fue reemplazada por `tblRolModulo`/`tblRolSubModulo`).

---

### tblRolModulo — Módulos activos por Rol

```sql
CREATE TABLE tblRolModulo (
    id_RolModulo BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_Rol BIGINT UNSIGNED NOT NULL,
    id_Modulos BIGINT UNSIGNED NOT NULL,
    fechaAlta DATETIME NOT NULL,
    activo TINYINT(1) NOT NULL,
    PRIMARY KEY (id_RolModulo),
    FOREIGN KEY (id_Rol) REFERENCES tblRoles(id_Rol),
    FOREIGN KEY (id_Modulos) REFERENCES tblModulos(id_Modulos),
    UNIQUE (id_Rol, id_Modulos)
);
```

| Columna | Tipo | Nullable | Default | FK |
|---------|------|----------|---------|-----|
| id_RolModulo | bigint PK | No | auto_increment | — |
| id_Rol | bigint FK | No | — | → tblRoles.id_Rol |
| id_Modulos | bigint FK | No | — | → tblModulos.id_Modulos |
| fechaAlta | datetime | No | — | — |
| activo | boolean | No | — | — |

> Nota: cada fila indica que un rol tiene activo el módulo completo (su sección en el menú). Los roles con nombre que contiene "admin" o igual a "root" tienen todos los módulos sin necesidad de filas aquí. Reemplaza a las tablas `tblRolPermiso` y `tblPermisoUsuarios` (conservadas sin uso por compatibilidad).

---

### tblRolSubModulo — Submódulos activos por Rol

```sql
CREATE TABLE tblRolSubModulo (
    id_RolSubModulo BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_Rol BIGINT UNSIGNED NOT NULL,
    id_SubModulos BIGINT UNSIGNED NOT NULL,
    fechaAlta DATETIME NOT NULL,
    activo TINYINT(1) NOT NULL,
    PRIMARY KEY (id_RolSubModulo),
    FOREIGN KEY (id_Rol) REFERENCES tblRoles(id_Rol),
    FOREIGN KEY (id_SubModulos) REFERENCES tblSubModulos(id_SubModulos),
    UNIQUE (id_Rol, id_SubModulos)
);
```

| Columna | Tipo | Nullable | Default | FK |
|---------|------|----------|---------|-----|
| id_RolSubModulo | bigint PK | No | auto_increment | — |
| id_Rol | bigint FK | No | — | → tblRoles.id_Rol |
| id_SubModulos | bigint FK | No | — | → tblSubModulos.id_SubModulos |
| fechaAlta | datetime | No | — | — |
| activo | boolean | No | — | — |

> Nota: define qué submódulos (enlaces del menú y rutas) puede usar un rol. La fila en `tblRolModulo` se mantiene sincronizada (un módulo queda activo si tiene al menos un submódulo activo, o si no tiene submódulos). El middleware `permiso:{clave}` valida por clave de submódulo, con respaldo al nombre del módulo. Los roles admin/root siempre tienen todo.

---

## Módulo 3: Tickets

### tblTipoSoporte — Tipos de Soporte

```sql
CREATE TABLE tblTipoSoporte (
    id_TipoSoporte BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    descripcion VARCHAR(100) NOT NULL,
    fechaAlta DATETIME NOT NULL,
    activo TINYINT(1) NOT NULL,
    id_Area BIGINT UNSIGNED NULL,
    PRIMARY KEY (id_TipoSoporte),
    FOREIGN KEY (id_Area) REFERENCES tblArea(id_Area)
);
```

| Columna | Tipo | Nullable | Default | FK |
|---------|------|----------|---------|-----|
| id_TipoSoporte | bigint PK | No | auto_increment | — |
| descripcion | varchar(100) | No | — | — |
| fechaAlta | datetime | No | — | — |
| activo | boolean | No | — | — |
| id_Area | bigint FK | Si | — | → tblArea.id_Area |

**Nota:** El id=1 se reserva para "Desarrollo" (excluido del dropdown de soporte)

---

### tblEstatus — Estatus

```sql
CREATE TABLE tblEstatus (
    id_Estatus BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    descripcion VARCHAR(100) NOT NULL,
    fechaAlta DATETIME NOT NULL,
    activo TINYINT(1) NOT NULL,
    id_Modulos BIGINT UNSIGNED NULL,
    PRIMARY KEY (id_Estatus),
    FOREIGN KEY (id_Modulos) REFERENCES tblModulos(id_Modulos)
);
```

| Columna | Tipo | Nullable | Default | FK |
|---------|------|----------|---------|-----|
| id_Estatus | bigint PK | No | auto_increment | — |
| descripcion | varchar(100) | No | — | — |
| fechaAlta | datetime | No | — | — |
| activo | boolean | No | — | — |
| id_Modulos | bigint FK | Si | — | → tblModulos.id_Modulos |

**Nota:** Los estatus de tickets usan `id_Modulos = 1`

---

### tblTicket — Tickets Principales

```sql
CREATE TABLE tblTicket (
    id_Ticket BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_Usuario BIGINT UNSIGNED NOT NULL,
    id_TipoSoporte BIGINT UNSIGNED NOT NULL,
    tipoTicket VARCHAR(20) NOT NULL DEFAULT 'falla_tecnica',
    id_Tecnico BIGINT UNSIGNED NULL,
    asunto VARCHAR(200) NOT NULL,
    problema TEXT NOT NULL,
    img VARCHAR(150) NULL,
    fechaAlta DATETIME NOT NULL,
    activo TINYINT(1) NOT NULL,
    id_Estatus BIGINT UNSIGNED NULL,
    id_Equipo BIGINT UNSIGNED NULL,
    vehiculos VARCHAR(255) NULL,
    problemaGps VARCHAR(200) NULL,
    fechaCierre DATETIME NULL,
    PRIMARY KEY (id_Ticket),
    FOREIGN KEY (id_Usuario) REFERENCES tblUsuario(id_Usuario),
    FOREIGN KEY (id_TipoSoporte) REFERENCES tblTipoSoporte(id_TipoSoporte),
    FOREIGN KEY (id_Tecnico) REFERENCES tblUsuario(id_Usuario),
    FOREIGN KEY (id_Estatus) REFERENCES tblEstatus(id_Estatus),
    FOREIGN KEY (id_Equipo) REFERENCES tblEquipo(id_Equipo)
);
```

| Columna | Tipo | Nullable | Default | FK |
|---------|------|----------|---------|-----|
| id_Ticket | bigint PK | No | auto_increment | — |
| id_Usuario | bigint FK | No | — | → tblUsuario.id_Usuario (creador) |
| id_TipoSoporte | bigint FK | No | — | → tblTipoSoporte.id_TipoSoporte |
| tipoTicket | varchar(20) | No | falla_tecnica | `falla_tecnica` / `instalacion_gps` / `revision_gps` |
| id_Tecnico | bigint FK | Si | — | → tblUsuario.id_Usuario (técnico) |
| asunto | varchar(200) | No | — | — |
| problema | text | No | — | — |
| img | varchar(150) | Si | — | — |
| fechaAlta | datetime | No | — | — |
| activo | boolean | No | — | — |
| id_Estatus | bigint FK | Si | — | → tblEstatus.id_Estatus |
| id_Equipo | bigint FK | Si | — | → tblEquipo.id_Equipo |
| vehiculos | varchar(255) | Si | — | ids de tblVehiculo separados por coma (instalación = varios, revisión = uno) |
| problemaGps | varchar(200) | Si | — | valor del catálogo `Ticket::PROBLEMAS_GPS` (o `Otro: …`) |
| fechaCierre | datetime | Si | — | — |

> **Nota producción:** `tipoTicket`, `vehiculos` y `problemaGps` se aplican en SQL Server con el script
> `database/sqlsrv/alter_tblTicket_tipo_ticket.sql` (pendiente de aprobación; NO ejecutar aún).

**Relaciones Eloquent:**
- `belongsTo` → Usuario (id_Usuario, como creador)
- `belongsTo` → Usuario (id_Tecnico, como técnico)
- `belongsTo` → TipoSoporte (id_TipoSoporte)
- `belongsTo` → Estatus (id_Estatus)
- `belongsTo` → Equipo (id_Equipo)
- `hasMany` → TicketComentario
- `hasMany` → TicketEvidencia
- `hasOne` → TicketDesarrolloDetalle

---

### tblTicketComentarios — Comentarios de Ticket

```sql
CREATE TABLE tblTicketComentarios (
    id_TicketComentario BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_Ticket BIGINT UNSIGNED NOT NULL,
    comentario TEXT NOT NULL,
    fechaComentario DATETIME NOT NULL,
    id_Usuario BIGINT UNSIGNED NOT NULL,
    id_Estatus BIGINT UNSIGNED NULL,
    fechaAlta DATETIME NOT NULL,
    activo TINYINT(1) NOT NULL,
    PRIMARY KEY (id_TicketComentario),
    FOREIGN KEY (id_Ticket) REFERENCES tblTicket(id_Ticket),
    FOREIGN KEY (id_Usuario) REFERENCES tblUsuario(id_Usuario),
    FOREIGN KEY (id_Estatus) REFERENCES tblEstatus(id_Estatus)
);
```

| Columna | Tipo | Nullable | Default | FK |
|---------|------|----------|---------|-----|
| id_TicketComentario | bigint PK | No | auto_increment | — |
| id_Ticket | bigint FK | No | — | → tblTicket.id_Ticket |
| comentario | text | No | — | — |
| fechaComentario | datetime | No | — | — |
| id_Usuario | bigint FK | No | — | → tblUsuario.id_Usuario |
| id_Estatus | bigint FK | Si | — | → tblEstatus.id_Estatus |
| fechaAlta | datetime | No | — | — |
| activo | boolean | No | — | — |

---

### tblTicketEvidencia — Evidencias / Archivos Adjuntos

```sql
CREATE TABLE tblTicketEvidencia (
    id_TicketEvidencia BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_Ticket BIGINT UNSIGNED NOT NULL,
    nombreArchivo VARCHAR(255) NOT NULL,
    rutaArchivo VARCHAR(500) NOT NULL,
    extension VARCHAR(20) NULL,
    tipoContenido VARCHAR(100) NULL,
    tamanioBytes BIGINT NULL,
    id_UsuarioAlta BIGINT UNSIGNED NOT NULL,
    fechaAlta DATETIME NOT NULL,
    activo TINYINT(1) NOT NULL,
    PRIMARY KEY (id_TicketEvidencia),
    FOREIGN KEY (id_Ticket) REFERENCES tblTicket(id_Ticket),
    FOREIGN KEY (id_UsuarioAlta) REFERENCES tblUsuario(id_Usuario)
);
```

| Columna | Tipo | Nullable | Default | FK |
|---------|------|----------|---------|-----|
| id_TicketEvidencia | bigint PK | No | auto_increment | — |
| id_Ticket | bigint FK | No | — | → tblTicket.id_Ticket |
| nombreArchivo | varchar(255) | No | — | — |
| rutaArchivo | varchar(500) | No | — | — |
| extension | varchar(20) | Si | — | — |
| tipoContenido | varchar(100) | Si | — | — |
| tamanioBytes | bigint | Si | — | — |
| id_UsuarioAlta | bigint FK | No | — | → tblUsuario.id_Usuario |
| fechaAlta | datetime | No | — | — |
| activo | boolean | No | — | — |

---

### tblTicketArchivo — Archivos de Ticket (NO USADO)

```sql
CREATE TABLE tblTicketArchivo (
    id_TicketArchivo BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_Ticket BIGINT UNSIGNED NOT NULL,
    id_Usuario BIGINT UNSIGNED NOT NULL,
    tipoArchivo VARCHAR(50) NOT NULL,
    ruta VARCHAR(250) NOT NULL,
    nombreOriginal VARCHAR(250) NULL,
    extension VARCHAR(20) NULL,
    fechaAlta DATETIME NOT NULL,
    activo TINYINT(1) NOT NULL,
    PRIMARY KEY (id_TicketArchivo),
    FOREIGN KEY (id_Ticket) REFERENCES tblTicket(id_Ticket),
    FOREIGN KEY (id_Usuario) REFERENCES tblUsuario(id_Usuario)
);
```

| Columna | Tipo | Nullable | Default | FK |
|---------|------|----------|---------|-----|
| id_TicketArchivo | bigint PK | No | auto_increment | — |
| id_Ticket | bigint FK | No | — | → tblTicket.id_Ticket |
| id_Usuario | bigint FK | No | — | → tblUsuario.id_Usuario |
| tipoArchivo | varchar(50) | No | — | — |
| ruta | varchar(250) | No | — | — |
| nombreOriginal | varchar(250) | Si | — | — |
| extension | varchar(20) | Si | — | — |
| fechaAlta | datetime | No | — | — |
| activo | boolean | No | — | — |

**Nota:** Modelo existe pero NO se usa en controladores. El sistema usa `tblTicketEvidencia` en su lugar.

---

### tblTipoDesarrollo — Tipos de Desarrollo

```sql
CREATE TABLE tblTipoDesarrollo (
    id_TipoDesarrollo BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL,
    enabled TINYINT(1) NOT NULL,
    descripcion TEXT NULL,
    PRIMARY KEY (id_TipoDesarrollo)
);
```

| Columna | Tipo | Nullable | Default | FK |
|---------|------|----------|---------|-----|
| id_TipoDesarrollo | bigint PK | No | auto_increment | — |
| nombre | varchar(50) | No | — | — |
| enabled | boolean | No | — | — |
| descripcion | text | Si | — | — |

---

### tblSistemas — Sistemas

```sql
CREATE TABLE tblSistemas (
    id_sistemas BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL,
    enabled TINYINT(1) NOT NULL,
    PRIMARY KEY (id_sistemas)
);
```

| Columna | Tipo | Nullable | Default | FK |
|---------|------|----------|---------|-----|
| id_sistemas | bigint PK | No | auto_increment | — |
| nombre | varchar(50) | No | — | — |
| enabled | boolean | No | — | — |

---

### tblTicketDesarrolloDetalle — Detalle de Ticket de Desarrollo

```sql
CREATE TABLE tblTicketDesarrolloDetalle (
    id_TicketDesarrolloDetalle BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_Ticket BIGINT UNSIGNED NULL,
    id_TipoDesarrollo BIGINT UNSIGNED NULL,
    id_Sistemas BIGINT UNSIGNED NULL,
    id_Modulo INT NULL,
    id_Submodulo INT NULL,
    aprobado TINYINT(1) NULL,
    id_UsuarioAprobo INT NULL,
    fechaAprobo DATETIME NULL,
    fechaEstimadaTermino DATETIME NULL,
    PRIMARY KEY (id_TicketDesarrolloDetalle),
    FOREIGN KEY (id_Ticket) REFERENCES tblTicket(id_Ticket),
    FOREIGN KEY (id_TipoDesarrollo) REFERENCES tblTipoDesarrollo(id_TipoDesarrollo),
    FOREIGN KEY (id_Sistemas) REFERENCES tblSistemas(id_sistemas)
);
```

| Columna | Tipo | Nullable | Default | FK |
|---------|------|----------|---------|-----|
| id_TicketDesarrolloDetalle | bigint PK | No | auto_increment | — |
| id_Ticket | bigint FK | Si | — | → tblTicket.id_Ticket |
| id_TipoDesarrollo | bigint FK | Si | — | → tblTipoDesarrollo.id_TipoDesarrollo |
| id_Sistemas | bigint FK | Si | — | → tblSistemas.id_sistemas |
| id_Modulo | int | Si | — | → tblModulosSistemas.id_ModulosSistemas (lógica) |
| id_Submodulo | int | Si | — | → tblSubmodulosSistemas.id_SubmodulosSistemas (lógica) |
| aprobado | boolean | Si | — | — |
| id_UsuarioAprobo | int | Si | — | → tblUsuario.id_Usuario (lógica) |
| fechaAprobo | datetime | Si | — | — |
| fechaEstimadaTermino | datetime | Si | — | — |

**Nota:** `id_Modulo`, `id_Submodulo`, `id_UsuarioAprobo` son FKs lógicas sin constraint a nivel BD.

---

### tblNotificaciones — Notificaciones

```sql
CREATE TABLE tblNotificaciones (
    id_Notificacion BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_UsuarioDestino BIGINT UNSIGNED NOT NULL,
    Mensaje VARCHAR(500) NOT NULL,
    Tipo VARCHAR(50) NOT NULL,
    UrlAccion VARCHAR(200) NULL,
    Leido TINYINT(1) NULL,
    FechaCreacion DATETIME NULL,
    PRIMARY KEY (id_Notificacion),
    FOREIGN KEY (id_UsuarioDestino) REFERENCES tblUsuario(id_Usuario)
);
```

| Columna | Tipo | Nullable | Default | FK |
|---------|------|----------|---------|-----|
| id_Notificacion | bigint PK | No | auto_increment | — |
| id_UsuarioDestino | bigint FK | No | — | → tblUsuario.id_Usuario |
| Mensaje | varchar(500) | No | — | — |
| Tipo | varchar(50) | No | — | — |
| UrlAccion | varchar(200) | Si | — | — |
| Leido | boolean | Si | — | — |
| FechaCreacion | datetime | Si | — | — |

**Nota:** No usa convención `fechaAlta`/`activo` — usa `FechaCreacion`/`Leido`.

**Valores de `Tipo`:** el vocabulario legacy es snake_case (`ticket_asignado`, `comentario`). La app Laravel ahora también escribe filas aquí desde `TicketNotificacionService` (al crear o reasignar un ticket) con `ticket_asignado` y `ticket_reasignado`; y al cerrarse un ticket por inactividad (`tickets:cerrar-inactivos`) con `ticket_cerrado`, destinado al solicitante y al técnico, con `UrlAccion = route('tickets.show', id)`. El endpoint `GET api/notificaciones` no filtra por `Tipo`.

---

## Módulo 4: Inventario / Equipos

### tblTipoEquipo — Tipos de Equipo

```sql
CREATE TABLE tblTipoEquipo (
    id_TipoEquipo BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    descripcion VARCHAR(250) NOT NULL,
    fechaAlta DATETIME NOT NULL,
    activo TINYINT(1) NOT NULL,
    PRIMARY KEY (id_TipoEquipo)
);
```

| Columna | Tipo | Nullable | Default | FK |
|---------|------|----------|---------|-----|
| id_TipoEquipo | bigint PK | No | auto_increment | — |
| descripcion | varchar(250) | No | — | — |
| fechaAlta | datetime | No | — | — |
| activo | boolean | No | — | — |

---

### tblVehiculo — Catálogo de Vehículos / Unidades GPS

> Nueva (`2026_10_05_000001_crear_tblvehiculo_y_tipo_ticket_tblticket.php`). CRUD en
> **Inventario → Vehículos** (`inventario.vehiculo.*`, permiso `vehiculos`). Las unidades se vinculan a
> tickets de tipo `instalacion_gps` / `revision_gps` vía la columna `tblTicket.vehiculos`.

```sql
CREATE TABLE tblVehiculo (
    id_Vehiculo BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    numeroUnidad VARCHAR(50) NOT NULL,
    tipoVehiculo VARCHAR(30) NOT NULL,      -- 'Tractocamión' | 'Camión'
    fechaAlta DATETIME NOT NULL,
    activo TINYINT(1) NOT NULL,
    PRIMARY KEY (id_Vehiculo),
    UNIQUE KEY (numeroUnidad)
);
```

| Columna | Tipo | Nullable | Default | FK |
|---------|------|----------|---------|-----|
| id_Vehiculo | bigint PK | No | auto_increment | — |
| numeroUnidad | varchar(50) | No | — | única |
| tipoVehiculo | varchar(30) | No | — | catálogo `Vehiculo::TIPOS` |
| fechaAlta | datetime | No | — | — |
| activo | boolean | No | — | — |

---

### tblEquipoCampo / tblTipoEquipoCampo — ELIMINADAS

> Módulo de "Campos Personalizados" eliminado por la migración `2026_09_28_000005_reemplazar_campos_personalizados.php`. Fue reemplazado por columnas fijas en `tblEquipo` (`serieMotherboard`, `procesador`, `memoriaRam`, `almacenamiento`, `sistemaOperativo`, `ip`, `mac`, `estadoGarantia`, `fechaFinGarantia`) y el campo de texto libre `especificaciones`.

---

### tblEquipo — Equipos / Activos

```sql
CREATE TABLE tblEquipo (
    id_Equipo BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    marca VARCHAR(150) NOT NULL,
    modelo VARCHAR(150) NOT NULL,
    id_TipoEquipo BIGINT UNSIGNED NOT NULL,
    id_Estatus BIGINT UNSIGNED NOT NULL,
    fechaAdquisicion DATETIME NULL,
    numeroSerie VARCHAR(150) NULL,
    serieMotherboard VARCHAR(150) NULL,
    procesador VARCHAR(150) NULL,
    memoriaRam VARCHAR(100) NULL,
    almacenamiento VARCHAR(150) NULL,
    tamanoAlmacenamiento VARCHAR(50) NULL,
    sistemaOperativo VARCHAR(100) NULL,
    ip VARCHAR(45) NULL,
    mac VARCHAR(50) NULL,
    estadoGarantia VARCHAR(50) NULL,
    fechaFinGarantia DATE NULL,
    especificaciones TEXT NULL,
    tipoTeclado VARCHAR(100) NULL,
    conectividad VARCHAR(100) NULL,
    tipoImpresora VARCHAR(50) NULL,
    colorImpresion VARCHAR(20) NULL,
    multifuncion TINYINT(1) NULL,
    funciones VARCHAR(150) NULL,
    tipoCamara VARCHAR(50) NULL,
    resolucion VARCHAR(10) NULL,
    conexion VARCHAR(30) NULL,
    tipoGps VARCHAR(50) NULL,
    imei VARCHAR(20) NULL,
    operador VARCHAR(50) NULL,
    lineaSim VARCHAR(20) NULL,
    capacidad VARCHAR(50) NULL,
    voltajeEntrada VARCHAR(20) NULL,
    voltajeSalida VARCHAR(20) NULL,
    contactos SMALLINT UNSIGNED NULL,
    tamano VARCHAR(50) NULL,
    puertos VARCHAR(255) NULL,
    costo DECIMAL(12,2) NULL,
    nombreEquipo VARCHAR(150) NULL,
    ubicacion VARCHAR(150) NULL,
    observaciones TEXT NULL,
    id_PeriodoMantenimiento INT NOT NULL,
    fechaUltimoMantenimiento DATETIME NULL,
    fechaAlta DATETIME NOT NULL,
    activo TINYINT(1) NOT NULL,
    id_Oficinas BIGINT UNSIGNED NULL,
    esCompartido TINYINT(1) NOT NULL,
    PRIMARY KEY (id_Equipo),
    FOREIGN KEY (id_TipoEquipo) REFERENCES tblTipoEquipo(id_TipoEquipo),
    FOREIGN KEY (id_Estatus) REFERENCES tblEstatus(id_Estatus),
    FOREIGN KEY (id_Oficinas) REFERENCES tblOficinas(id_Oficinas)
);
```

| Columna | Tipo | Nullable | Default | FK |
|---------|------|----------|---------|-----|
| id_Equipo | bigint PK | No | auto_increment | — |
| marca | varchar(150) | No | — | — |
| modelo | varchar(150) | No | — | — |
| id_TipoEquipo | bigint FK | No | — | → tblTipoEquipo.id_TipoEquipo |
| id_Estatus | bigint FK | No | — | → tblEstatus.id_Estatus |
| fechaAdquisicion | datetime | Si | — | — |
| numeroSerie | varchar(150) | Si | — | — |
| serieMotherboard | varchar(150) | Si | — | — |
| procesador | varchar(150) | Si | — | — |
| memoriaRam | varchar(100) | Si | — | — |
| almacenamiento | varchar(150) | Si | — | — |
| tamanoAlmacenamiento | varchar(50) | Si | — | — |
| sistemaOperativo | varchar(100) | Si | — | — |
| ip | varchar(45) | Si | — | — |
| mac | varchar(50) | Si | — | — |
| estadoGarantia | varchar(50) | Si | — | — |
| fechaFinGarantia | date | Si | — | — |
| especificaciones | text | Si | — | — |
| tipoTeclado | varchar(100) | Si | — | — |
| conectividad | varchar(100) | Si | — | — |
| tipoImpresora | varchar(50) | Si | — | — |
| colorImpresion | varchar(20) | Si | — | — |
| multifuncion | boolean | Si | — | — |
| funciones | varchar(150) | Si | — | — |
| tipoCamara | varchar(50) | Si | — | — |
| resolucion | varchar(10) | Si | — | — |
| conexion | varchar(30) | Si | — | — |
| tipoGps | varchar(50) | Si | — | — |
| imei | varchar(20) | Si | — | — |
| operador | varchar(50) | Si | — | — |
| lineaSim | varchar(20) | Si | — | — |
| capacidad | varchar(50) | Si | — | — |
| voltajeEntrada | varchar(20) | Si | — | — |
| voltajeSalida | varchar(20) | Si | — | — |
| contactos | smallint unsigned | Si | — | — |
| tamano | varchar(50) | Si | — | — |
| puertos | varchar(255) | Si | — | — |
| costo | decimal(12,2) | Si | — | — |
| nombreEquipo | varchar(150) | Si | — | — |
| ubicacion | varchar(150) | Si | — | — |
| observaciones | text | Si | — | — |
| id_PeriodoMantenimiento | int | No | — | → tblPeriodoMantenimiento.id_PeriodoMantenimiento (lógica) |
| fechaUltimoMantenimiento | datetime | Si | — | — |
| fechaAlta | datetime | No | — | — |
| activo | boolean | No | — | — |
| id_Oficinas | bigint FK | Si | — | → tblOficinas.id_Oficinas |
| esCompartido | boolean | No | — | — |

**Nota:** `id_PeriodoMantenimiento` es FK lógica sin constraint a nivel BD.

> **Marca y Modelo se capturan en texto libre para TODOS los tipos**
> (`con_marca_dropdown` y `con_modelo_dropdown` vacíos en
> `config/equipo_perfiles.php`). Solo `ubicacion` usa el catálogo de
> Departamentos (tipos 1–8). Las tablas `tblCatMarca`/`tblCatModelo`
> siguen gestionables en Configuración pero ya no alimentan los formularios.
>
> Para el tipo **PC Escritorio (CPU)** (`id_TipoEquipo = 1`),
> `ubicacion` (Departamentos), `procesador`, `almacenamiento` (tipo) y
> `sistemaOperativo` se capturan desde los catálogos de abajo y
> `tamanoAlmacenamiento` guarda el tamaño por separado (ej. "512 GB").
> Para los demás tipos, ver las notas por tipo más abajo.
>
> Para el tipo **Monitor** (`id_TipoEquipo = 2`), `ubicacion` se captura
> desde Departamentos, y usa campos propios:
> `tamano` (ej. "24 pulgadas"), `puertos` (checkboxes + texto libre unidos con
> coma, ej. "HDMI, DisplayPort, USB"), `costo`, `fechaAdquisicion`. Al guardar,
> los campos que no aplican al Monitor (período/último mantenimiento, serie
> motherboard, procesador, memoria RAM, almacenamiento, sistema operativo, ip,
> mac) se guardan en `NULL`.
>
> Para los tipos **Teclado** (`3`) y **Mouse** (`4`): `ubicacion` usa
> Departamentos, `marca` en texto libre, `modelo` **no es obligatorio** (si
> no se captura se guarda `''` porque la columna es `NOT NULL`) y se guardan
> `fechaAdquisicion` y `costo`. Teclado usa `tipoTeclado` (catálogo
> `tblCatTipoTeclado`) y ambos usan `conectividad` (catálogo
> `tblCatConectividad`); el resto de campos PC se guardan en `NULL` y el
> período de mantenimiento en `0`.
>
> Para el tipo **Regulador** (`5`): `ubicacion` usa Departamentos, `marca` y
> `modelo` en texto libre, `modelo` sí es obligatorio, y usa campos propios `capacidad`,
> `voltajeEntrada`, `voltajeSalida` y `contactos` (entero 0–999), más
> `fechaAdquisicion` y `costo`. Los campos PC se guardan en `NULL` y el
> período en `0`.
>
> Para el tipo **Laptop** (`6`): usa los **mismos catálogos y campos que PC
> Escritorio** (`marca` y `modelo` en texto libre, ubicación desde
> Departamentos, procesador, tipo de almacenamiento, sistema operativo en catálogo y
> `tamanoAlmacenamiento`; `modelo` obligatorio; serie motherboard, memoria
> RAM, IP, MAC y período/último mantenimiento visibles) **más**
> `fechaAdquisicion` y `costo`. Sin columnas nuevas: todo vive en las
> columnas existentes de `tblEquipo`.
>
> Los valores de `tipoTeclado`, `conectividad`, `tamano`, `puertos` y
> `capacidad`/`voltaje*` se guardan como **texto** en `tblEquipo` (sin FK),
> igual que el resto de los catálogos de equipo, para conservar el histórico.
>
> Para el tipo **Impresora** (`7`): `marca` y `modelo` en texto libre,
> `ubicacion` desde Departamentos, `modelo` obligatorio, y usa campos propios `tipoImpresora`
> (catálogo `tblCatTipoImpresora`), `colorImpresion` (`Color` /
> `Monocromática`), `multifuncion` (1/0, "Sí"/"No") y `funciones`
> (checkboxes unidos con coma: "Imprimir, Copiar"), más `numeroSerie`,
> `fechaAdquisicion`, `costo`, `ip`, `estadoGarantia`/`fechaFinGarantia` y
> `especificaciones`. **No** se muestran
> serie motherboard ni memoria RAM (`sin_internos` en
> `config/equipo_perfiles.php`); los campos PC (procesador, almacenamiento,
> SO) se guardan en `NULL` y el período en `0`.
>
> Para el tipo **Cámara** (`8`): `marca` y `modelo` se capturan en **texto
> libre** (como GPS/Celular), `ubicacion` desde dropdown (Departamentos), y
> usa campos propios `tipoCamara` (catálogo `tblCatTipoCamara`),
> `resolucion` (`1MP`/`2MP`/`4MP`/`5MP`/`8MP`) y `conexion`
> (`PoE`/`Cableado`/`Inalámbrica`), más `numeroSerie`, `fechaAdquisicion`,
> `costo`, `ip`, `estadoGarantia`/`fechaFinGarantia` y `especificaciones`.
> **No** se muestran
> serie motherboard ni memoria RAM (`sin_internos`); los campos PC se guardan
> en `NULL` y el período en `0`.
>
> Para el tipo **GPS** (`9`): `marca` y `modelo` en texto libre,
> `ubicacion` en texto libre (no usa Departamentos), y usa campos propios
> `tipoGps` (catálogo `tblCatTipoGps`), `imei` (exactamente 15 dígitos),
> `operador` (lista fija `operadores_gps` en `config/equipo_perfiles.php`:
> Telcel, AT&T, Movistar, Unefon, Otro) y `lineaSim`, más `numeroSerie`,
> `fechaAdquisicion`, `costo`, `estadoGarantia`/`fechaFinGarantia` y
> `especificaciones`. **No** se muestran serie motherboard, memoria RAM, IP,
> MAC ni los campos PC en texto libre (procesador, almacenamiento y sistema
> operativo) — `sin_internos` + `sin_textos_pc`; sí se muestran período y
> último mantenimiento.

---

### Catálogos de Equipo — tblCatMarca / tblCatModelo / tblCatProcesador / tblCatSistemaOperativo / tblCatTipoAlmacenamiento / tblCatTipoTeclado / tblCatConectividad / tblCatTipoImpresora / tblCatTipoCamara / tblCatTipoGps

Catálogos que alimentan los dropdowns de los formularios de equipo.
`tblCatMarca` y `tblCatModelo` ya **no alimentan los formularios** (Marca y
Modelo son texto libre en todos los tipos) pero siguen gestionables en
Configuración; el resto sí alimenta dropdowns: procesador, SO y tipo de
almacenamiento (PC/Laptop), tipo de teclado y conectividad (Teclado/Mouse),
tipo de impresora (Impresora), tipo de cámara (Cámara) y tipo de GPS (GPS).
Se gestionan desde **Configuración → Catálogos de Equipo**
(`configuracion/catalogoEquipo.*`); la migración `2026_10_02_000003` crea los
primeros 5, `2026_10_03_000003` crea los 2 siguientes, `2026_10_05_000002`
crea `tblCatTipoImpresora`, `2026_10_05_000003` crea `tblCatTipoCamara` y
`2026_10_06_000001` crea `tblCatTipoGps`,
con semilla inicial + los valores ya usados en equipos existentes.

| Tabla | PK | Columnas | FK |
|-------|----|----------|----|
| tblCatMarca | id_Marca | nombre (unique), fechaAlta, activo | — |
| tblCatModelo | id_Modelo | id_Marca, nombre, fechaAlta, activo | id_Marca → tblCatMarca |
| tblCatProcesador | id_Procesador | nombre (unique), fechaAlta, activo | — |
| tblCatSistemaOperativo | id_SistemaOperativo | nombre (unique), fechaAlta, activo | — |
| tblCatTipoAlmacenamiento | id_TipoAlmacenamiento | nombre (unique), fechaAlta, activo | — |
| tblCatTipoTeclado | id_TipoTeclado | nombre (unique), fechaAlta, activo | — |
| tblCatConectividad | id_Conectividad | nombre (unique), fechaAlta, activo | — |
| tblCatTipoImpresora | id_TipoImpresora | nombre (unique), fechaAlta, activo | — |
| tblCatTipoCamara | id_TipoCamara | nombre (unique), fechaAlta, activo | — |
| tblCatTipoGps | id_TipoGps | nombre (unique), fechaAlta, activo | — |

> Semilla de `tblCatTipoTeclado`: Mecánico, Membrana, Ergonómico.
> Semilla de `tblCatConectividad`: USB, Inalámbrico, Bluetooth, PS/2.
> Semilla de `tblCatTipoImpresora`: Láser, Inyección de tinta, Matricial, Térmica.
> Semilla de `tblCatTipoCamara`: Domo, Bala, PTZ, Cónica.
> Semilla de `tblCatTipoGps`: Vehicular, Motocicleta, Personal, Patrimonial.

> `tblEquipo` guarda los **valores** de estos catálogos como texto en sus
> columnas (`marca`, `modelo`, etc.) — no hay FK — para conservar el histórico
> aunque el catálogo cambie. En edición, un valor que ya no exista en el
> catálogo se muestra como opción extra para no perderlo.

---

### tblEquipoDetalle — ELIMINADA

> Eliminada por la migración `2026_09_28_000005_reemplazar_campos_personalizados.php`. Los valores de "Serie Motherboard" fueron migrados a `tblEquipo.serieMotherboard`.

---

### tblEquipoUsuario — Asignación Equipo ↔ Usuario

```sql
CREATE TABLE tblEquipoUsuario (
    id_EquipoUsuario BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_Equipo BIGINT UNSIGNED NOT NULL,
    id_Usuario BIGINT UNSIGNED NOT NULL,
    fechaAsignacion DATETIME NOT NULL,
    fechaBaja DATETIME NULL,
    observaciones TEXT NULL,
    id_Area BIGINT UNSIGNED NULL,
    fechaAlta DATETIME NOT NULL,
    activo TINYINT(1) NOT NULL,
    PRIMARY KEY (id_EquipoUsuario),
    FOREIGN KEY (id_Equipo) REFERENCES tblEquipo(id_Equipo),
    FOREIGN KEY (id_Usuario) REFERENCES tblUsuario(id_Usuario),
    FOREIGN KEY (id_Area) REFERENCES tblArea(id_Area)
);
```

| Columna | Tipo | Nullable | Default | FK |
|---------|------|----------|---------|-----|
| id_EquipoUsuario | bigint PK | No | auto_increment | — |
| id_Equipo | bigint FK | No | — | → tblEquipo.id_Equipo |
| id_Usuario | bigint FK | No | — | → tblUsuario.id_Usuario |
| fechaAsignacion | datetime | No | — | — |
| fechaBaja | datetime | Si | — | — |
| observaciones | text | Si | — | — |
| id_Area | bigint FK | Si | — | → tblArea.id_Area |
| fechaAlta | datetime | No | — | — |
| activo | boolean | No | — | — |

---

### tblHistorialEquipoUsuario — Historial de Asignaciones

```sql
CREATE TABLE tblHistorialEquipoUsuario (
    id_HistorialEquipoUsuario BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_EquipoUsuarioOriginal INT NULL,
    id_Equipo BIGINT UNSIGNED NOT NULL,
    id_Usuario BIGINT UNSIGNED NOT NULL,
    fechaAsignacion DATETIME NOT NULL,
    fechaBaja DATETIME NULL,
    observaciones TEXT NULL,
    id_Area BIGINT UNSIGNED NULL,
    fechaAlta DATETIME NOT NULL,
    fechaAccion DATETIME NOT NULL,
    id_UsuarioAccion INT NOT NULL,
    accion VARCHAR(100) NOT NULL,
    motivo VARCHAR(500) NULL,
    equipoMarca VARCHAR(150) NULL,
    equipoModelo VARCHAR(150) NULL,
    equipoNombre VARCHAR(150) NULL,
    equipoNumeroSerie VARCHAR(150) NULL,
    equipoUbicacion VARCHAR(150) NULL,
    PRIMARY KEY (id_HistorialEquipoUsuario),
    FOREIGN KEY (id_Equipo) REFERENCES tblEquipo(id_Equipo),
    FOREIGN KEY (id_Usuario) REFERENCES tblUsuario(id_Usuario),
    FOREIGN KEY (id_Area) REFERENCES tblArea(id_Area)
);
```

| Columna | Tipo | Nullable | Default | FK |
|---------|------|----------|---------|-----|
| id_HistorialEquipoUsuario | bigint PK | No | auto_increment | — |
| id_EquipoUsuarioOriginal | int | **Si** | — | → tblEquipoUsuario.id_EquipoUsuario (lógica) |
| id_Equipo | bigint FK | No | — | → tblEquipo.id_Equipo |
| id_Usuario | bigint FK | No | — | → tblUsuario.id_Usuario |
| fechaAsignacion | datetime | No | — | — |
| fechaBaja | datetime | Si | — | — |
| observaciones | text | Si | — | — |
| id_Area | bigint FK | Si | — | → tblArea.id_Area |
| fechaAlta | datetime | No | — | — |
| fechaAccion | datetime | No | — | — |
| id_UsuarioAccion | int | No | — | → tblUsuario.id_Usuario (lógica) |
| accion | varchar(100) | No | — | — |
| motivo | varchar(500) | Si | — | — |
| equipoMarca | varchar(150) | Si | — | — |
| equipoModelo | varchar(150) | Si | — | — |
| equipoNombre | varchar(150) | Si | — | — |
| equipoNumeroSerie | varchar(150) | Si | — | — |
| equipoUbicacion | varchar(150) | Si | — | — |

**Nota:** `id_EquipoUsuarioOriginal` e `id_UsuarioAccion` son FKs lógicas sin constraint.

---

### tblTipoRelacionEquipo — Tipos de Relación entre Equipos

> **SIN MIGRACIÓN** — Tabla creada fuera de Laravel.

```sql
CREATE TABLE tblTipoRelacionEquipo (
    id_TipoRelacionEquipo BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    descripcion VARCHAR(100) NOT NULL,
    fechaAlta DATETIME NOT NULL,
    activo TINYINT(1) NOT NULL,
    PRIMARY KEY (id_TipoRelacionEquipo)
);
```

| Columna | Tipo | Nullable | Default | FK |
|---------|------|----------|---------|-----|
| id_TipoRelacionEquipo | bigint PK | No | auto_increment | — |
| descripcion | varchar(100) | No | — | — |
| fechaAlta | datetime | No | — | — |
| activo | boolean | No | — | — |

---

### tblEquipoRelacion — Relación Padre-Hijo entre Equipos

> **SIN MIGRACIÓN** — Tabla creada fuera de Laravel.

```sql
CREATE TABLE tblEquipoRelacion (
    id_EquipoRelacion BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_EquipoPadre BIGINT UNSIGNED NOT NULL,
    id_EquipoHijo BIGINT UNSIGNED NOT NULL,
    id_TipoRelacionEquipo BIGINT UNSIGNED NOT NULL,
    fechaAsignacion DATE NOT NULL,
    fechaBaja DATE NULL,
    observaciones TEXT NULL,
    fechaAlta DATETIME NOT NULL,
    activo TINYINT(1) NOT NULL,
    PRIMARY KEY (id_EquipoRelacion)
);
```

| Columna | Tipo | Nullable | Default | FK |
|---------|------|----------|---------|-----|
| id_EquipoRelacion | bigint PK | No | auto_increment | — |
| id_EquipoPadre | bigint FK | No | — | → tblEquipo.id_Equipo (lógica) |
| id_EquipoHijo | bigint FK | No | — | → tblEquipo.id_Equipo (lógica) |
| id_TipoRelacionEquipo | bigint FK | No | — | → tblTipoRelacionEquipo.id_TipoRelacionEquipo (lógica) |
| fechaAsignacion | date | No | — | — |
| fechaBaja | date | Si | — | — |
| observaciones | text | Si | — | — |
| fechaAlta | datetime | No | — | — |
| activo | boolean | No | — | — |

**Nota:** Todas las FKs son lógicas sin constraint a nivel BD.

---

## Módulo 5: Desarrollo / Sistemas

### tblModulosSistemas — Módulos por Sistema

> **SIN MIGRACIÓN** — Tabla creada fuera de Laravel.

```sql
CREATE TABLE tblModulosSistemas (
    id_ModulosSistemas BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL,
    id_sistemas BIGINT UNSIGNED NOT NULL,
    enabled TINYINT(1) NOT NULL,
    PRIMARY KEY (id_ModulosSistemas)
);
```

| Columna | Tipo | Nullable | Default | FK |
|---------|------|----------|---------|-----|
| id_ModulosSistemas | bigint PK | No | auto_increment | — |
| nombre | varchar(50) | No | — | — |
| id_sistemas | bigint FK | No | — | → tblSistemas.id_sistemas (lógica) |
| enabled | boolean | No | — | — |

---

### tblSubmodulosSistemas — Submódulos por Sistema

> **SIN MIGRACIÓN** — Tabla creada fuera de Laravel.

```sql
CREATE TABLE tblSubmodulosSistemas (
    id_SubmodulosSistemas BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL,
    id_ModulosSistemas BIGINT UNSIGNED NOT NULL,
    enabled TINYINT(1) NOT NULL,
    PRIMARY KEY (id_SubmodulosSistemas)
);
```

| Columna | Tipo | Nullable | Default | FK |
|---------|------|----------|---------|-----|
| id_SubmodulosSistemas | bigint PK | No | auto_increment | — |
| nombre | varchar(50) | No | — | — |
| id_ModulosSistemas | bigint FK | No | — | → tblModulosSistemas.id_ModulosSistemas (lógica) |
| enabled | boolean | No | — | — |

---

## Módulo 6: Mantenimiento

### tblPeriodoMantenimiento — Períodos de Mantenimiento

```sql
CREATE TABLE tblPeriodoMantenimiento (
    id_PeriodoMantenimiento BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    descripcion VARCHAR(50) NOT NULL,
    meses INT NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id_PeriodoMantenimiento)
);
```

| Columna | Tipo | Nullable | Default | FK |
|---------|------|----------|---------|-----|
| id_PeriodoMantenimiento | bigint PK | No | auto_increment | — |
| descripcion | varchar(50) | No | — | — |
| meses | int | No | — | — |
| activo | boolean | No | true | — |

**Seed data:**
| id | descripcion | meses |
|----|-------------|-------|
| 1 | Mensual | 1 |
| 2 | Bimestral | 2 |
| 3 | Trimestral | 3 |
| 4 | Semestral | 6 |
| 5 | Anual | 12 |

---

### tblMantenimiento — Registros de Mantenimiento

```sql
CREATE TABLE tblMantenimiento (
    id_Mantenimiento BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_Equipo BIGINT UNSIGNED NOT NULL,
    id_UsuarioTecnico BIGINT UNSIGNED NOT NULL,
    fechaProgramada DATETIME NOT NULL,
    fechaEjecucion DATETIME NULL,
    id_Estatus BIGINT UNSIGNED NULL,
    tipoMantenimiento VARCHAR(50) NOT NULL DEFAULT 'Preventivo',
    observaciones TEXT NULL,
    estadoInicial VARCHAR(50) NULL,
    hw_encendido CHAR(2) NULL,
    hw_pantalla CHAR(2) NULL,
    hw_ventilacion CHAR(2) NULL,
    hw_dispositivos CHAR(2) NULL,
    sw_so CHAR(2) NULL,
    sw_seguridad CHAR(2) NULL,
    sw_antivirus CHAR(2) NULL,
    sw_rendimiento CHAR(2) NULL,
    act_limpieza TINYINT(1) NOT NULL DEFAULT 0,
    act_revision_hw TINYINT(1) NOT NULL DEFAULT 0,
    act_actualizacion_so TINYINT(1) NOT NULL DEFAULT 0,
    act_instalacion_sw TINYINT(1) NOT NULL DEFAULT 0,
    act_parches TINYINT(1) NOT NULL DEFAULT 0,
    act_eliminacion TINYINT(1) NOT NULL DEFAULT 0,
    act_diagnostico TINYINT(1) NOT NULL DEFAULT 0,
    act_otro TINYINT(1) NOT NULL DEFAULT 0,
    act_otro_detalle TEXT NULL,
    conclusion VARCHAR(100) NULL,
    reporteCerrado TINYINT(1) NOT NULL DEFAULT 0,
    id_Ticket BIGINT UNSIGNED NULL,
    fechaAlta DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id_Mantenimiento),
    FOREIGN KEY (id_Equipo) REFERENCES tblEquipo(id_Equipo),
    FOREIGN KEY (id_UsuarioTecnico) REFERENCES tblUsuario(id_Usuario),
    FOREIGN KEY (id_Estatus) REFERENCES tblEstatus(id_Estatus),
    FOREIGN KEY (id_Ticket) REFERENCES tblTicket(id_Ticket)
);
```

| Columna | Tipo | Nullable | Default | FK |
|---------|------|----------|---------|-----|
| id_Mantenimiento | bigint PK | No | auto_increment | — |
| id_Equipo | bigint FK | No | — | → tblEquipo.id_Equipo |
| id_UsuarioTecnico | bigint FK | No | — | → tblUsuario.id_Usuario |
| fechaProgramada | datetime | No | — | — |
| fechaEjecucion | datetime | Si | — | — |
| id_Estatus | bigint FK | Si | — | → tblEstatus.id_Estatus |
| tipoMantenimiento | varchar(50) | No | 'Preventivo' | — |
| observaciones | text | Si | — | — |
| estadoInicial | varchar(50) | Si | — | — |
| hw_encendido | char(2) | Si | — | — |
| hw_pantalla | char(2) | Si | — | — |
| hw_ventilacion | char(2) | Si | — | — |
| hw_dispositivos | char(2) | Si | — | — |
| sw_so | char(2) | Si | — | — |
| sw_seguridad | char(2) | Si | — | — |
| sw_antivirus | char(2) | Si | — | — |
| sw_rendimiento | char(2) | Si | — | — |
| act_limpieza | boolean | No | false | — |
| act_revision_hw | boolean | No | false | — |
| act_actualizacion_so | boolean | No | false | — |
| act_instalacion_sw | boolean | No | false | — |
| act_parches | boolean | No | false | — |
| act_eliminacion | boolean | No | false | — |
| act_diagnostico | boolean | No | false | — |
| act_otro | boolean | No | false | — |
| act_otro_detalle | text | Si | — | — |
| conclusion | varchar(100) | Si | — | — |
| reporteCerrado | boolean | No | false | — |
| id_Ticket | bigint FK | Si | — | → tblTicket.id_Ticket |
| fechaAlta | datetime | No | CURRENT_TIMESTAMP | — |
| activo | boolean | No | true | — |

---

## Módulo 7: Configuración

### tblConfiguracionSistema — Configuración Key-Value

```sql
CREATE TABLE tblConfiguracionSistema (
    id_ConfiguracionSistema BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clave VARCHAR(100) NOT NULL,
    valor TEXT NOT NULL,
    descripcion VARCHAR(250) NULL,
    fechaAlta DATETIME NOT NULL,
    activo TINYINT(1) NOT NULL,
    PRIMARY KEY (id_ConfiguracionSistema)
);
```

| Columna | Tipo | Nullable | Default | FK |
|---------|------|----------|---------|-----|
| id_ConfiguracionSistema | bigint PK | No | auto_increment | — |
| clave | varchar(100) | No | — | — |
| valor | text | No | — | — |
| descripcion | varchar(250) | Si | — | — |
| fechaAlta | datetime | No | — | — |
| activo | boolean | No | — | — |

---

### tblTipoEquipoTicket — Tipo de Equipo para Tickets

> **SIN MIGRACIÓN** — Tabla creada fuera de Laravel.

```sql
CREATE TABLE tblTipoEquipoTicket (
    id_TipoEquipoTicket BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clave VARCHAR(50) NOT NULL,
    descripcion VARCHAR(250) NOT NULL,
    fechaAlta DATETIME NOT NULL,
    activo TINYINT(1) NOT NULL,
    PRIMARY KEY (id_TipoEquipoTicket)
);
```

| Columna | Tipo | Nullable | Default | FK |
|---------|------|----------|---------|-----|
| id_TipoEquipoTicket | bigint PK | No | auto_increment | — |
| clave | varchar(50) | No | — | — |
| descripcion | varchar(250) | No | — | — |
| fechaAlta | datetime | No | — | — |
| activo | boolean | No | — | — |

---

## Diagrama de Relaciones (Texto)

```
                              ┌──────────────┐
                              │   tblDepto   │
                              └──────┬───────┘
                                     │ 1:N
                                     ▼
┌──────────────┐  1:N  ┌──────────────────────┐  1:1  ┌──────────┐
│   tblArea    │──────►│      tblUsuario      │◄─────│ tblLogin │
└──────┬───────┘       │                      │       └──────────┘
       │               │  N:M (tblRolesUsuarios)  N:M (tblEquipoUsuario)
       │               └──────┬───────┬───────┘
       │                      │       │
       │                      ▼       ▼
       │               ┌──────────┐  ┌──────────────┐
       │               │ tblRoles │  │   tblEquipo  │
       │               └────┬─────┘  └──────┬───────┘
       │                    │               │
        │               1:N  │               │ 1:N
        │                    ▼               ▼
         │            ┌──────────────┐  ┌──────────────────┐
         │            │ tblRolModulo │  │  tblEquipoUsuario│
         │            └──────┬───────┘  └──────────────────┘
         │                   │ 1:N
        │                   ▼
        │            ┌─────────────────┐
        │            │ tblRolSubModulo │──► tblSubModulos
        │            └─────────────────┘
       │
       │  1:N
       ▼
┌──────────────┐  1:N  ┌──────────────────┐
│tblTipoSoporte│──────►│    tblTicket      │
└──────────────┘       │                    │──► tblTicketComentarios
                       │                    │──► tblTicketEvidencia
                       │                    │──► tblTicketDesarrolloDetalle
                       │                    │──► tblMantenimiento
                       └──────────────────┘

┌──────────────────┐     ┌──────────────────────┐
│tblSistemas       │────►│tblModulosSistemas     │──► tblSubmodulosSistemas
└──────────────────┘     └──────────────────────┘

┌──────────────────────┐     ┌──────────────────────┐
│tblModulos            │────►│tblSubModulos         │
└──────────────────────┘     └──────────────────────┘

┌──────────────────────┐
│tblConfiguracionSistema│  (key-value, sin relaciones)
└──────────────────────┘

┌──────────────────────┐
│tblNotificaciones     │──► tblUsuario (id_UsuarioDestino)
└──────────────────────┘

┌─────────────────────────┐
│tblTipoRelacionEquipo    │──► tblEquipoRelacion (padre/hijo)
└─────────────────────────┘
```

---

## Tablas sin Migración Laravel

Estas tablas existen en la BD pero NO tienen archivo de migración en `database/migrations/`:

| # | Tabla | Modelo | Uso |
|---|-------|--------|-----|
| 1 | `tblEquipoRelacion` | `EquipoRelacion` | Relación padre-hijo entre equipos |
| 2 | `tblTipoRelacionEquipo` | `TipoRelacionEquipo` | Catálogo de tipos de relación |
| 3 | `tblModulosSistemas` | `ModuloSistema` | Módulos por sistema (para tickets desarrollo) |
| 4 | `tblSubmodulosSistemas` | `SubModuloSistema` | Submódulos por sistema |
| 5 | `tblTipoEquipoTicket` | `TipoEquipoTicket` | Tipo de equipo para tickets |

**Recomendación:** Crear una migración que incluya estas tablas para mantener consistencia.

---

## Tablas con Seeder (datos iniciales)

| Tabla | Seeder | Datos |
|-------|--------|-------|
| `tblPeriodoMantenimiento` | `PeriodoMantenimientoSeeder` | Mensual(1), Bimestral(2), Trimestral(3), Semestral(6), Anual(12) |

---

## Notas para Crear Migraciones Laravel

1. **Orden de creación:** Respetar el orden de dependencias FK. Crear primero tablas padre:
   - `tblDepto`, `tblArea`, `tblTipoEmpleado`, `tblOficinas` (sin dependencias)
   - `tblUsuario` (depende de las 4 anteriores)
   - `tblLogin` (depende de tblUsuario)
   - `tblRoles` → `tblRolesUsuarios`
    - `tblModulos` → `tblSubModulos`
    - `tblEstatus` → `tblTipoSoporte`
   - `tblTipoEquipo` → `tblEquipo`
   - `tblTicket` (depende de tblUsuario, tblTipoSoporte, tblEstatus, tblEquipo)
   - Tablas de ticket (Comentarios, Evidencia, etc.)
   - Tablas de sistema (ModulosSistemas, SubmodulosSistemas)
   - `tblPeriodoMantenimiento` → `tblMantenimiento`

2. **FKs lógicas:** Las columnas `id_EquipoCampo`, `id_Modulo`, `id_Submodulo`, `id_UsuarioAprobo`, `id_PeriodoMantenimiento`, `id_EquipoUsuarioOriginal` son enters sin constraint. Usar `$table->integer('columna')` en vez de `foreignId`.

3. **Soft deletes:** Todas las tablas usan `$table->boolean('activo')` en vez de `$table->softDeletes()`.

4. **Timestamps:** Ninguna tabla usa `$table->timestamps()`. Usar `$table->dateTime('fechaAlta')` manual.

5. **Tabla dual:** `tblUsuario` es referenciada por dos modelos (`User` y `Usuario`). No duplicar la tabla.

6. **Typo conocido:** La columna `descipcion` en `tblRoles` tiene un error ortográfico (falta "r").

7. **Tablas sin migración:** Crear migración separada o incluirla en la migración principal para las 5 tablas faltantes.

---

## Resumen de Migraciones Existentes

| Archivo | Tablas creadas/modificadas |
|---------|---------------------------|
| `2025_01_01_000001_create_helpdesk_tables.php` | 30 tablas (todas las principales) |
| `2026_09_22_000001_create_tblPeriodoMantenimiento_table.php` | tblPeriodoMantenimiento |
| `2026_09_22_000002_create_tblMantenimiento_table.php` | tblMantenimiento |
| `2026_09_22_000003_add_fechaUltimoMantenimiento_to_tblEquipo_table.php` | Agrega columna a tblEquipo |
| `2026_09_23_000001_add_report_fields_to_tblMantenimiento_table.php` | Agrega columnas a tblMantenimiento |
| `2026_09_23_000002_add_reporteCerrado_to_tblMantenimiento_table.php` | Agrega columna a tblMantenimiento |
| `2026_09_23_204145_alter_tbl_historial_equipo_usuario_...` | Modifica tblHistorialEquipoUsuario |
| `2026_09_28_000001_create_tblRolModulo_table.php` | tblRolModulo (convierte datos de la entonces legacy tblRolPermiso) |
| `2026_09_28_000002_add_clave_to_tblSubModulos_and_create_tblRolSubModulo.php` | columna `clave` en tblSubModulos (+ submódulos mantenimientos/roles-modulos/gps/dashboard) y tblRolSubModulo (convierte datos de tblRolModulo) |
| `2026_09_28_000004_limpieza_modulos_y_tablas_legacy.php` | elimina tablas `tblRolPermiso`, `tblPermisoUsuarios`, `tblAcciones` y los módulos/submódulos no usados (GPS, Nuevo, categoría soporte, etc.) |
| `2026_09_28_000005_reemplazar_campos_personalizados.php` | agrega 10 columnas a tblEquipo y elimina tblEquipoCampo, tblTipoEquipoCampo y tblEquipoDetalle (migra "Serie Motherboard") |
| `2026_10_02_000001_eliminar_submodulos_modulos_y_roles_usuarios.php` | elimina los submódulos `modulos` y `roles-usuarios` de tblSubModulos + sus filas en tblRolSubModulo (pantallas retiradas del menú) |
| `2026_10_02_000002_sincronizar_catalogo_modulos_desde_config.php` | refleja `config/modulos.php` en tblModulos/tblSubModulos (elimina claves fuera del registro, p. ej. `submodulos` y `nuevo`) |
| `2026_10_02_000003_crear_catalogos_equipo.php` | crea las 5 tablas de catálogo de equipo (marca, modelo, procesador, SO, tipo almacenamiento) con semilla inicial |
| `2026_10_02_000004_agregar_tamano_almacenamiento_a_tblequipo.php` | agrega `tblequipo.tamanoAlmacenamiento` (tamaño del almacenamiento de PC Escritorio) |
| `2026_10_03_000001_agregar_campos_monitor_a_tblequipo.php` | agrega `tblequipo.tamano`, `tblequipo.puertos` y `tblequipo.costo` (campos del tipo Monitor) |
| `2026_10_03_000002_agregar_campos_perifericos_a_tblequipo.php` | agrega `tblequipo.tipoTeclado`, `conectividad`, `capacidad`, `voltajeEntrada`, `voltajeSalida` y `contactos` (campos de Teclado, Mouse y Regulador) |
| `2026_10_03_000003_crear_catalogos_teclado_conectividad.php` | crea `tblCatTipoTeclado` y `tblCatConectividad` con semilla inicial |
| `2026_10_05_000001_crear_tblvehiculo_y_tipo_ticket_tblticket.php` | crea `tblVehiculo` y agrega `tblTicket.tipoTicket`, `tblTicket.vehiculos` y `tblTicket.problemaGps` (en producción: `database/sqlsrv/alter_tblTicket_tipo_ticket.sql`) |
| `2026_10_05_000002_crear_catalogo_tipo_impresora_y_campos_impresora.php` | crea `tblCatTipoImpresora` con semilla y agrega `tblEquipo.tipoImpresora`, `colorImpresion`, `multifuncion` y `funciones` (en producción: `database/sqlsrv/alter_tblequipo_impresora.sql`) |
| `2026_10_05_000003_crear_catalogo_tipo_camara_y_campos_camara.php` | crea `tblCatTipoCamara` con semilla y agrega `tblEquipo.tipoCamara`, `resolucion` y `conexion` (en producción: `database/sqlsrv/alter_tblequipo_camara.sql`) |
| `2026_10_06_000001_crear_catalogo_tipo_gps_y_campos_gps.php` | crea `tblCatTipoGps` con semilla y agrega `tblEquipo.tipoGps`, `imei`, `operador` y `lineaSim` (en producción: `database/sqlsrv/alter_tblequipo_gps.sql`) |
