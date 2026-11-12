-- ============================================================
-- HelpDesk — Script para PRODUCTIVO (SQL Server)
-- Fecha: 2026-11-11
-- Descripción: Tabla de documentos FO-IT-03 (Recepción de equipo)
--              del módulo ISO 9001 — Generador de Documentos.
--
-- ⚠️ EJECUTAR SOLO TRAS APROBACIÓN. La migración de Laravel
--    (2026_11_11_120000) ya aplica esto en la BD local MySQL.
--
-- Notas:
--   * Sin FK a tblUsuario (el PK de tblUsuario es INT en
--     productivo y BIGINT en local; la relación es lógica).
--   * motivoEntrega / aplicacionGastos: selección única
--     (catálogos del modelo IsoRecepcionEquipo).
--   * tipoActivo / accesorios: selección múltiple como lista
--     separada por comas.
--   * firma* guarda el PNG en data-URI
--     ("data:image/png;base64,...") → nvarchar(max).
--   * huella: SHA-256 en hex (64 caracteres) de la información
--     del documento + firmas; se recalcula al crear y al firmar.
--   * Borrado lógico vía activo = 0 (auditoría ISO).
-- ============================================================

IF OBJECT_ID('dbo.tblIsoRecepcionEquipo', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.tblIsoRecepcionEquipo (
        id_RecepcionEquipo   BIGINT         IDENTITY(1,1) NOT NULL CONSTRAINT PK_tblIsoRecepcionEquipo PRIMARY KEY,
        fecha                DATE           NOT NULL,
        motivoEntrega        VARCHAR(50)    NOT NULL,
        tipoActivo           VARCHAR(500)   NOT NULL,
        marca                VARCHAR(100)   NOT NULL,
        modelo               VARCHAR(100)   NOT NULL,
        noSerie              VARCHAR(100)   NOT NULL,
        imei                 VARCHAR(100)   NULL,
        numeroTelefonico     VARCHAR(50)    NULL,
        accesorios           VARCHAR(500)   NOT NULL,
        descripcionEntrega   VARCHAR(500)   NOT NULL,
        aplicacionGastos     VARCHAR(50)    NOT NULL,
        gastosReparacion     DECIMAL(12,2)  NOT NULL CONSTRAINT DF_tblIsoRecepcionEquipo_gastos DEFAULT (0),
        revisadoPor          VARCHAR(150)   NOT NULL,
        entregadoPor         VARCHAR(150)   NOT NULL,
        id_Usuario           BIGINT         NOT NULL,
        fechaCreacion        DATETIME       NOT NULL,
        activo               TINYINT        NOT NULL CONSTRAINT DF_tblIsoRecepcionEquipo_activo DEFAULT (1),
        firmaRevision        NVARCHAR(MAX)  NULL,
        fechaFirmaRevision   DATETIME       NULL,
        firmaEntrega         NVARCHAR(MAX)  NULL,
        fechaFirmaEntrega    DATETIME       NULL,
        huella               VARCHAR(64)    NULL
    );
END
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = 'IX_tblIsoRecepcionEquipo_id_Usuario'
      AND object_id = OBJECT_ID('dbo.tblIsoRecepcionEquipo')
)
    CREATE INDEX IX_tblIsoRecepcionEquipo_id_Usuario ON dbo.tblIsoRecepcionEquipo (id_Usuario);
GO
