-- ============================================================
-- HelpDesk — Script para PRODUCTIVO (SQL Server)
-- Fecha: 2026-10-05
-- Descripción: Campos del tipo Cámara (id 8) + catálogo de
--              tipos de cámara.
--
-- ⚠️ EJECUTAR SOLO TRAS APROBACIÓN. La migración de Laravel
--    (2026_10_05_000003) ya aplica esto en la BD de pruebas MySQL.
-- ============================================================

-- 1. Catálogo de tipos de cámara
IF OBJECT_ID('dbo.tblCatTipoCamara', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.tblCatTipoCamara (
        id_TipoCamara INT IDENTITY(1,1) NOT NULL CONSTRAINT PK_tblCatTipoCamara PRIMARY KEY,
        nombre        VARCHAR(100) NOT NULL,
        fechaAlta     DATETIME     NOT NULL,
        activo        TINYINT      NOT NULL,
        CONSTRAINT UQ_tblCatTipoCamara_nombre UNIQUE (nombre)
    );

    INSERT INTO dbo.tblCatTipoCamara (nombre, fechaAlta, activo) VALUES
        ('Domo',   GETDATE(), 1),
        ('Bala',   GETDATE(), 1),
        ('PTZ',    GETDATE(), 1),
        ('Cónica', GETDATE(), 1);
END
GO

-- 2. Columnas nuevas en tblEquipo
IF COL_LENGTH('dbo.tblEquipo', 'tipoCamara') IS NULL
    ALTER TABLE dbo.tblEquipo ADD tipoCamara VARCHAR(50) NULL;
GO

IF COL_LENGTH('dbo.tblEquipo', 'resolucion') IS NULL
    ALTER TABLE dbo.tblEquipo ADD resolucion VARCHAR(10) NULL;
GO

IF COL_LENGTH('dbo.tblEquipo', 'conexion') IS NULL
    ALTER TABLE dbo.tblEquipo ADD conexion VARCHAR(30) NULL;
GO
