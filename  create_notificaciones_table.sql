IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'tblNotificaciones')
BEGIN
    CREATE TABLE tblNotificaciones (
        id_Notificacion INT IDENTITY(1,1) PRIMARY KEY,
        id_UsuarioDestino INT NOT NULL,
        Mensaje NVARCHAR(500) NOT NULL,
        Tipo NVARCHAR(50) NOT NULL,
        UrlAccion NVARCHAR(200) NULL,
        Leido BIT DEFAULT 0,
        FechaCreacion DATETIME DEFAULT GETDATE(),
        FOREIGN KEY (id_UsuarioDestino) REFERENCES tblUsuario(id_Usuario)
    );
    PRINT 'Tabla tblNotificaciones creada exitosamente';
END
ELSE
    PRINT 'La tabla tblNotificaciones ya existe';
