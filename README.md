# HELPDESK - Sistema de Gestion de Tickets

Sistema web para la gestion de tickets de soporte tecnico, inventario de equipos, control de empleados y configuracion del sistema. Desarrollado en ASP.NET Core 8.0 con SQL Server.

## Modulos principales

- **Tickets**: Creacion, seguimiento y cierre de tickets de soporte con evidencias adjuntas
- **Inventario**: Gestion de equipos, tipos de equipo, relaciones y asignacion a usuarios
- **Empleados**: Administracion de usuarios, departamentos, areas y tipos de empleado
- **Configuracion**: Roles, permisos, modulos, submodulos, acciones, oficinas y estatus
- **Dashboard**: Panel principal con resumen de actividad
- **Correo**: Envio de notificaciones por correo electronico (MailKit)

## Tecnologias

- ASP.NET Core 8.0 (MVC)
- SQL Server (Microsoft.Data.SqlClient)
- MailKit 4.16.0
- Bootstrap 5
- jQuery

## Requisitos

- .NET 8.0 SDK o superior
- SQL Server (2016 o superior)
- Visual Studio 2022 o cualquier editor compatible con .NET

## Instalacion

### 1. Clonar el repositorio

```bash
git clone https://github.com/AlcomexPUE/helpdesk.git
cd helpdesk
```

### 2. Configurar la base de datos

Abre el archivo `appsettings.json` y modifica la cadena de conexion:

```json
{
  "ConnectionStrings": {
    "DefaultConnection": "Server=TU_SERVIDOR;Database=HELPDESK;User Id=USUARIO;Password=CONTRASENA;TrustServerCertificate=True;MultipleActiveResultSets=True;"
  }
}
```

### 3. Restaurar paquetes

```bash
dotnet restore
```

### 4. Ejecutar la aplicacion

```bash
dotnet run
```

La aplicacion estara disponible en: `http://localhost:5015`

### 5. Publicar para produccion

```bash
dotnet publish -c Release -o ./publish
```

Los archivos de salida quedaran en la carpeta `./publish`.

## Estructura del proyecto

```
HELPDESK/
├── Controllers/          # Controladores MVC por modulo
├── Models/               # Modelos de datos por modulo
├── Views/                # Vistas Razor por modulo
├── Services/             # Servicios (DB, Correo, Permisos)
├── wwwroot/              # Archivos estaticos (CSS, JS, lib)
├── Properties/           # Configuracion de inicio
├── EvidenciasTickets/    # Evidencias adjuntas a tickets
├── Program.cs            # Punto de entrada
└── appsettings.json      # Configuracion de la aplicacion
```

## Configuracion de correo

La configuracion de correo se almacena en la base de datos (`tblConfiguracionSistema`). Las claves necesarias son:

| Clave | Descripcion |
|---|---|
| `CorreoNotificacionesActivo` | Activar/desactivar envio (`1` o `true`) |
| `CorreoServidorSmtp` | Servidor SMTP |
| `CorreoPuertoSmtp` | Puerto SMTP (default: 587) |
| `CorreoRemitente` | Correo remitente |
| `CorreoUsuario` | Usuario de autenticacion SMTP |
| `CorreoPassword` | Password de autenticacion SMTP |
| `CorreoNombreRemitente` | Nombre que aparece como remitente |

## Licencia

Proyecto privado - Alcomex PUE
