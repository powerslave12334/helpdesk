-- ============================================================
-- HelpDesk — Script para PRODUCTIVO (SQL Server)
-- Fecha: 2026-10-05
-- Descripción: Campos del tipo Impresora (id 7) + catálogo de
--              tipos de impresora.
--
-- ⚠️ EJECUTAR SOLO TRAS APROBACIÓN. La migración de Laravel
--    (2026_10_05_000002) ya aplica esto en la BD de pruebas MySQL.
-- ============================================================

-- 1. Catálogo de tipos de impresora
IF OBJECT_ID('dbo.tblCatTipoImpresora', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.tblCatTipoImpresora (
        id_TipoImpresora INT IDENTITY(1,1) NOT NULL CONSTRAINT PK_tblCatTipoImpresora PRIMARY KEY,
        nombre           VARCHAR(100) NOT NULL,
        fechaAlta        DATETIME     NOT NULL,
        activo           TINYINT      NOT NULL,
        CONSTRAINT UQ_tblCatTipoImpresora_nombre UNIQUE (nombre)
    );

    INSERT INTO dbo.tblCatTipoImpresora (nombre, fechaAlta, activo) VALUES
        ('Láser',            GETDATE(), 1),
        ('Inyección de tinta', GETDATE(), 1),
        ('Matricial',        GETDATE(), 1),
        ('Térmica',          GETDATE(), 1);
END
GO

-- 2. Columnas nuevas en tblEquipo
IF COL_LENGTH('dbo.tblEquipo', 'tipoImpresora') IS NULL
    ALTER TABLE dbo.tblEquipo ADD tipoImpresora VARCHAR(50) NULL;
GO

IF COL_LENGTH('dbo.tblEquipo', 'colorImpresion') IS NULL
    ALTER TABLE dbo.tblEquipo ADD colorImpresion VARCHAR(20) NULL;
GO

IF COL_LENGTH('dbo.tblEquipo', 'multifuncion') IS NULL
    ALTER TABLE dbo.tblEquipo ADD multifuncion TINYINT NULL;
GO

IF COL_LENGTH('dbo.tblEquipo', 'funciones') IS NULL
    ALTER TABLE dbo.tblEquipo ADD funciones VARCHAR(150) NULL;
GO
