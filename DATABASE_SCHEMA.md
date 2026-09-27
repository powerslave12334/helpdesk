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

---

### tblSubModulos — Submódulos

```sql
CREATE TABLE tblSubModulos (
    id_SubModulos BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_Modulos BIGINT UNSIGNED NOT NULL,
    nombre VARCHAR(50) NOT NULL,
    controller VARCHAR(100) NULL,
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
| action | varchar(50) | Si | — | — |
| fechaAlta | datetime | No | — | — |
| activo | boolean | No | — | — |
| orden | int | Si | — | — |

---

### tblAcciones — Acciones (Consultar, Crear, etc.)

```sql
CREATE TABLE tblAcciones (
    id_Accion BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL,
    fechaAlta DATETIME NOT NULL,
    activo TINYINT(1) NOT NULL,
    PRIMARY KEY (id_Accion)
);
```

| Columna | Tipo | Nullable | Default | FK |
|---------|------|----------|---------|-----|
| id_Accion | bigint PK | No | auto_increment | — |
| nombre | varchar(50) | No | — | — |
| fechaAlta | datetime | No | — | — |
| activo | boolean | No | — | — |

---

### tblRolPermiso — Permisos por Rol

```sql
CREATE TABLE tblRolPermiso (
    id_RolPermiso BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_Rol BIGINT UNSIGNED NOT NULL,
    id_Modulos BIGINT UNSIGNED NOT NULL,
    id_SubModulos BIGINT UNSIGNED NOT NULL,
    id_Accion BIGINT UNSIGNED NOT NULL,
    permitido TINYINT(1) NOT NULL,
    fechaAlta DATETIME NOT NULL,
    activo TINYINT(1) NOT NULL,
    PRIMARY KEY (id_RolPermiso),
    FOREIGN KEY (id_Rol) REFERENCES tblRoles(id_Rol),
    FOREIGN KEY (id_Modulos) REFERENCES tblModulos(id_Modulos),
    FOREIGN KEY (id_SubModulos) REFERENCES tblSubModulos(id_SubModulos),
    FOREIGN KEY (id_Accion) REFERENCES tblAcciones(id_Accion)
);
```

| Columna | Tipo | Nullable | Default | FK |
|---------|------|----------|---------|-----|
| id_RolPermiso | bigint PK | No | auto_increment | — |
| id_Rol | bigint FK | No | — | → tblRoles.id_Rol |
| id_Modulos | bigint FK | No | — | → tblModulos.id_Modulos |
| id_SubModulos | bigint FK | No | — | → tblSubModulos.id_SubModulos |
| id_Accion | bigint FK | No | — | → tblAcciones.id_Accion |
| permitido | boolean | No | — | — |
| fechaAlta | datetime | No | — | — |
| activo | boolean | No | — | — |

---

### tblPermisoUsuarios — Permisos por Usuario

```sql
CREATE TABLE tblPermisoUsuarios (
    id_PermisoUsuarios BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_Usuario BIGINT UNSIGNED NOT NULL,
    id_Modulos BIGINT UNSIGNED NOT NULL,
    id_SubModulos BIGINT UNSIGNED NOT NULL,
    id_Accion BIGINT UNSIGNED NOT NULL,
    permitido TINYINT(1) NOT NULL,
    fechaAlta DATETIME NOT NULL,
    activo TINYINT(1) NOT NULL,
    PRIMARY KEY (id_PermisoUsuarios),
    FOREIGN KEY (id_Usuario) REFERENCES tblUsuario(id_Usuario),
    FOREIGN KEY (id_Modulos) REFERENCES tblModulos(id_Modulos),
    FOREIGN KEY (id_SubModulos) REFERENCES tblSubModulos(id_SubModulos),
    FOREIGN KEY (id_Accion) REFERENCES tblAcciones(id_Accion)
);
```

| Columna | Tipo | Nullable | Default | FK |
|---------|------|----------|---------|-----|
| id_PermisoUsuarios | bigint PK | No | auto_increment | — |
| id_Usuario | bigint FK | No | — | → tblUsuario.id_Usuario |
| id_Modulos | bigint FK | No | — | → tblModulos.id_Modulos |
| id_SubModulos | bigint FK | No | — | → tblSubModulos.id_SubModulos |
| id_Accion | bigint FK | No | — | → tblAcciones.id_Accion |
| permitido | boolean | No | — | — |
| fechaAlta | datetime | No | — | — |
| activo | boolean | No | — | — |

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
    id_Tecnico BIGINT UNSIGNED NULL,
    asunto VARCHAR(200) NOT NULL,
    problema TEXT NOT NULL,
    img VARCHAR(150) NULL,
    fechaAlta DATETIME NOT NULL,
    activo TINYINT(1) NOT NULL,
    id_Estatus BIGINT UNSIGNED NULL,
    id_Equipo BIGINT UNSIGNED NULL,
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
| id_Tecnico | bigint FK | Si | — | → tblUsuario.id_Usuario (técnico) |
| asunto | varchar(200) | No | — | — |
| problema | text | No | — | — |
| img | varchar(150) | Si | — | — |
| fechaAlta | datetime | No | — | — |
| activo | boolean | No | — | — |
| id_Estatus | bigint FK | Si | — | → tblEstatus.id_Estatus |
| id_Equipo | bigint FK | Si | — | → tblEquipo.id_Equipo |
| fechaCierre | datetime | Si | — | — |

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

### tblEquipoCampo — Definición de Campos (Catálogo)

> **SIN MIGRACIÓN** — Tabla creada fuera de Laravel. Se pobla con seeders (60+ campos).

```sql
CREATE TABLE tblEquipoCampo (
    id_EquipoCampo BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(250) NULL,
    fechaAlta DATETIME NOT NULL,
    activo TINYINT(1) NOT NULL,
    PRIMARY KEY (id_EquipoCampo)
);
```

| Columna | Tipo | Nullable | Default | FK |
|---------|------|----------|---------|-----|
| id_EquipoCampo | bigint PK | No | auto_increment | — |
| nombre | varchar(100) | No | — | — |
| descripcion | varchar(250) | Si | — | — |
| fechaAlta | datetime | No | — | — |
| activo | boolean | No | — | — |

**Seeders:** `EquipoCampoSeeder.php` — Procesador, Memoria RAM, Disco Duro, SO, etc.

---

### tblTipoEquipoCampo — Relación Tipo Equipo ↔ Campo

```sql
CREATE TABLE tblTipoEquipoCampo (
    id_TipoEquipoCampo BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_TipoEquipo BIGINT UNSIGNED NOT NULL,
    id_EquipoCampo INT NOT NULL,
    requerido TINYINT(1) NOT NULL,
    fechaAlta DATETIME NOT NULL,
    activo TINYINT(1) NOT NULL,
    PRIMARY KEY (id_TipoEquipoCampo),
    FOREIGN KEY (id_TipoEquipo) REFERENCES tblTipoEquipo(id_TipoEquipo)
);
```

| Columna | Tipo | Nullable | Default | FK |
|---------|------|----------|---------|-----|
| id_TipoEquipoCampo | bigint PK | No | auto_increment | — |
| id_TipoEquipo | bigint FK | No | — | → tblTipoEquipo.id_TipoEquipo |
| id_EquipoCampo | int | No | — | → tblEquipoCampo.id_EquipoCampo (lógica) |
| requerido | boolean | No | — | — |
| fechaAlta | datetime | No | — | — |
| activo | boolean | No | — | — |

**Nota:** `id_EquipoCampo` es FK lógica sin constraint a nivel BD.

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

---

### tblEquipoDetalle — Detalle / Valores de Campos del Equipo

```sql
CREATE TABLE tblEquipoDetalle (
    id_EquipoDetalle BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_Equipo BIGINT UNSIGNED NOT NULL,
    id_EquipoCampo INT NOT NULL,
    valor VARCHAR(250) NULL,
    fechaAlta DATETIME NOT NULL,
    activo TINYINT(1) NOT NULL,
    PRIMARY KEY (id_EquipoDetalle),
    FOREIGN KEY (id_Equipo) REFERENCES tblEquipo(id_Equipo)
);
```

| Columna | Tipo | Nullable | Default | FK |
|---------|------|----------|---------|-----|
| id_EquipoDetalle | bigint PK | No | auto_increment | — |
| id_Equipo | bigint FK | No | — | → tblEquipo.id_Equipo |
| id_EquipoCampo | int | No | — | → tblEquipoCampo.id_EquipoCampo (lógica) |
| valor | varchar(250) | Si | — | — |
| fechaAlta | datetime | No | — | — |
| activo | boolean | No | — | — |

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
       │            │tblRolPermiso │  │tblEquipoDetalle  │──► tblEquipoCampo
       │            └──────────────┘  └──────────────────┘
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

## Tablas sin Migración Laravel (5)

Estas tablas existen en la BD pero NO tienen archivo de migración en `database/migrations/`:

| # | Tabla | Modelo | Uso |
|---|-------|--------|-----|
| 1 | `tblEquipoCampo` | `EquipoCampoDefinicion` | Catálogo de campos de equipo (seeded) |
| 2 | `tblEquipoRelacion` | `EquipoRelacion` | Relación padre-hijo entre equipos |
| 3 | `tblTipoRelacionEquipo` | `TipoRelacionEquipo` | Catálogo de tipos de relación |
| 4 | `tblModulosSistemas` | `ModuloSistema` | Módulos por sistema (para tickets desarrollo) |
| 5 | `tblSubmodulosSistemas` | `SubModuloSistema` | Submódulos por sistema |
| 6 | `tblTipoEquipoTicket` | `TipoEquipoTicket` | Tipo de equipo para tickets |

**Recomendación:** Crear una migración que incluya estas 5 tablas para mantener consistencia.

---

## Tablas con Seeder (datos iniciales)

| Tabla | Seeder | Datos |
|-------|--------|-------|
| `tblPeriodoMantenimiento` | `PeriodoMantenimientoSeeder` | Mensual(1), Bimestral(2), Trimestral(3), Semestral(6), Anual(12) |
| `tblEquipoCampo` | `EquipoCampoSeeder` | 60+ campos: Procesador, Memoria RAM, Disco Duro, SO, etc. |

---

## Notas para Crear Migraciones Laravel

1. **Orden de creación:** Respetar el orden de dependencias FK. Crear primero tablas padre:
   - `tblDepto`, `tblArea`, `tblTipoEmpleado`, `tblOficinas` (sin dependencias)
   - `tblUsuario` (depende de las 4 anteriores)
   - `tblLogin` (depende de tblUsuario)
   - `tblRoles` → `tblRolesUsuarios`
   - `tblModulos` → `tblSubModulos`
   - `tblAcciones`
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
