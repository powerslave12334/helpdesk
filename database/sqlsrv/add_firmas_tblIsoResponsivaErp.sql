-- ============================================================
-- HelpDesk — Script para PRODUCTIVO (SQL Server)
-- Fecha: 2026-11-10
-- Descripción: Firmas digitales (visual + huella SHA-256) para
--              los documentos FO-IT-01 del módulo ISO 9001.
--
-- ⚠️ EJECUTAR SOLO TRAS APROBACIÓN. La migración de Laravel
--    (2026_11_10_141814) ya aplica esto en la BD local MySQL.
--
-- Notas:
--   * firma* guarda el PNG en data-URI
--     ("data:image/png;base64,...") → nvarchar(max).
--   * huella: SHA-256 en hex (64 caracteres) de la información
--     del documento + firmas; se recalcula al crear y al firmar.
-- ============================================================

IF COL_LENGTH('dbo.tblIsoResponsivaErp', 'firmaEmpleado') IS NULL
    ALTER TABLE dbo.tblIsoResponsivaErp ADD firmaEmpleado NVARCHAR(MAX) NULL;
GO

IF COL_LENGTH('dbo.tblIsoResponsivaErp', 'fechaFirmaEmpleado') IS NULL
    ALTER TABLE dbo.tblIsoResponsivaErp ADD fechaFirmaEmpleado DATETIME NULL;
GO

IF COL_LENGTH('dbo.tblIsoResponsivaErp', 'firmaTi') IS NULL
    ALTER TABLE dbo.tblIsoResponsivaErp ADD firmaTi NVARCHAR(MAX) NULL;
GO

IF COL_LENGTH('dbo.tblIsoResponsivaErp', 'fechaFirmaTi') IS NULL
    ALTER TABLE dbo.tblIsoResponsivaErp ADD fechaFirmaTi DATETIME NULL;
GO

IF COL_LENGTH('dbo.tblIsoResponsivaErp', 'huella') IS NULL
    ALTER TABLE dbo.tblIsoResponsivaErp ADD huella VARCHAR(64) NULL;
GO
