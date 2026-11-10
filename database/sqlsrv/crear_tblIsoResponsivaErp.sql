-- ============================================================
-- HelpDesk — Script para PRODUCTIVO (SQL Server)
-- Fecha: 2026-11-09
-- Descripción: Tabla de documentos FO-IT-01 (Responsiva ERP)
--              del módulo ISO 9001 — Generador de Documentos.
--
-- ⚠️ EJECUTAR SOLO TRAS APROBACIÓN. La migración de Laravel
--    (2026_11_09_163439) ya aplica esto en la BD local MySQL.
--
-- Notas:
--   * Sin FK a tblUsuario (el PK de tblUsuario es INT en
--     productivo y BIGINT en local; la relación es lógica).
--   * contrasenaErp almacena el valor cifrado con el cast
--     "encrypted" de Laravel (APP_KEY del entorno).
--   * Borrado lógico vía activo = 0 (auditoría ISO).
-- ============================================================

IF OBJECT_ID('dbo.tblIsoResponsivaErp', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.tblIsoResponsivaErp (
        id_ResponsivaErp BIGINT       IDENTITY(1,1) NOT NULL CONSTRAINT PK_tblIsoResponsivaErp PRIMARY KEY,
        fecha            DATE         NOT NULL,
        nombreEmpleado   VARCHAR(150) NOT NULL,
        puesto           VARCHAR(100) NOT NULL,
        correo           VARCHAR(150) NOT NULL,
        usuarioErp       VARCHAR(100) NOT NULL,
        contrasenaErp    VARCHAR(500) NOT NULL,
        id_Usuario       BIGINT       NOT NULL,
        fechaCreacion    DATETIME     NOT NULL,
        activo           TINYINT      NOT NULL CONSTRAINT DF_tblIsoResponsivaErp_activo DEFAULT (1)
    );
END
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = 'IX_tblIsoResponsivaErp_id_Usuario'
      AND object_id = OBJECT_ID('dbo.tblIsoResponsivaErp')
)
    CREATE INDEX IX_tblIsoResponsivaErp_id_Usuario ON dbo.tblIsoResponsivaErp (id_Usuario);
GO
