-- ============================================================
-- HelpDesk — Script para PRODUCTIVO (SQL Server)
-- Fecha: 2026-10-06
-- Descripción: Campos del tipo GPS (id 9) + catálogo de
--              tipos de GPS.
--
-- ⚠️ EJECUTAR SOLO TRAS APROBACIÓN. La migración de Laravel
--    (2026_10_06_000001) ya aplica esto en la BD de pruebas MySQL.
-- ============================================================

-- 1. Catálogo de tipos de GPS
IF OBJECT_ID('dbo.tblCatTipoGps', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.tblCatTipoGps (
        id_TipoGps INT IDENTITY(1,1) NOT NULL CONSTRAINT PK_tblCatTipoGps PRIMARY KEY,
        nombre     VARCHAR(100) NOT NULL,
        fechaAlta  DATETIME     NOT NULL,
        activo     TINYINT      NOT NULL,
        CONSTRAINT UQ_tblCatTipoGps_nombre UNIQUE (nombre)
    );

    INSERT INTO dbo.tblCatTipoGps (nombre, fechaAlta, activo) VALUES
        ('Vehicular',   GETDATE(), 1),
        ('Motocicleta', GETDATE(), 1),
        ('Personal',    GETDATE(), 1),
        ('Patrimonial', GETDATE(), 1);
END
GO

-- 2. Columnas nuevas en tblEquipo
IF COL_LENGTH('dbo.tblEquipo', 'tipoGps') IS NULL
    ALTER TABLE dbo.tblEquipo ADD tipoGps VARCHAR(50) NULL;
GO

IF COL_LENGTH('dbo.tblEquipo', 'imei') IS NULL
    ALTER TABLE dbo.tblEquipo ADD imei VARCHAR(20) NULL;
GO

IF COL_LENGTH('dbo.tblEquipo', 'operador') IS NULL
    ALTER TABLE dbo.tblEquipo ADD operador VARCHAR(50) NULL;
GO

IF COL_LENGTH('dbo.tblEquipo', 'lineaSim') IS NULL
    ALTER TABLE dbo.tblEquipo ADD lineaSim VARCHAR(20) NULL;
GO
