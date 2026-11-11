-- ============================================================
-- HelpDesk — Script para PRODUCTIVO (SQL Server)
-- Fecha: 2026-11-11
-- Descripción: Tablas de documentos FO-IT-02 (Acta de entrega)
--              del módulo ISO 9001 — Generador de Documentos.
--
-- ⚠️ EJECUTAR SOLO TRAS APROBACIÓN. La migración de Laravel
--    (2026_11_11_110000) ya aplica esto en la BD local MySQL.
--
-- Notas:
--   * Sin FK a tblUsuario (el PK de tblUsuario es INT en
--     productivo y BIGINT en local; la relación es lógica).
--   * tblIsoActaEntregaEquipo guarda los renglones dinámicos
--     de equipos (una fila por equipo entregado).
--   * firma* guarda el PNG en data-URI
--     ("data:image/png;base64,...") → nvarchar(max).
--   * huella: SHA-256 en hex (64 caracteres) de la información
--     del documento + renglones + firmas; se recalcula al crear
--     y al firmar.
--   * Borrado lógico vía activo = 0 (auditoría ISO).
-- ============================================================

IF OBJECT_ID('dbo.tblIsoActaEntrega', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.tblIsoActaEntrega (
        id_ActaEntrega      BIGINT        IDENTITY(1,1) NOT NULL CONSTRAINT PK_tblIsoActaEntrega PRIMARY KEY,
        fecha               DATE          NOT NULL,
        entregadoPor        VARCHAR(150)  NOT NULL,
        recibidoPor         VARCHAR(150)  NOT NULL,
        id_Usuario          BIGINT        NOT NULL,
        fechaCreacion       DATETIME      NOT NULL,
        activo              TINYINT       NOT NULL CONSTRAINT DF_tblIsoActaEntrega_activo DEFAULT (1),
        firmaEntrega        NVARCHAR(MAX) NULL,
        fechaFirmaEntrega   DATETIME      NULL,
        firmaRecepcion      NVARCHAR(MAX) NULL,
        fechaFirmaRecepcion DATETIME      NULL,
        huella              VARCHAR(64)   NULL
    );
END
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = 'IX_tblIsoActaEntrega_id_Usuario'
      AND object_id = OBJECT_ID('dbo.tblIsoActaEntrega')
)
    CREATE INDEX IX_tblIsoActaEntrega_id_Usuario ON dbo.tblIsoActaEntrega (id_Usuario);
GO

IF OBJECT_ID('dbo.tblIsoActaEntregaEquipo', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.tblIsoActaEntregaEquipo (
        id_ActaEquipo  BIGINT       IDENTITY(1,1) NOT NULL CONSTRAINT PK_tblIsoActaEntregaEquipo PRIMARY KEY,
        id_ActaEntrega BIGINT       NOT NULL,
        cantidad       INT          NOT NULL,
        descripcion    VARCHAR(255) NOT NULL,
        marca          VARCHAR(100) NULL,
        modelo         VARCHAR(100) NULL,
        noSerie        VARCHAR(100) NULL,
        condiciones    VARCHAR(50)  NOT NULL
    );
END
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = 'IX_tblIsoActaEntregaEquipo_id_ActaEntrega'
      AND object_id = OBJECT_ID('dbo.tblIsoActaEntregaEquipo')
)
    CREATE INDEX IX_tblIsoActaEntregaEquipo_id_ActaEntrega ON dbo.tblIsoActaEntregaEquipo (id_ActaEntrega);
GO
