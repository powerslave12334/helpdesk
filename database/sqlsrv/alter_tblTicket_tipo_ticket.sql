-- ============================================================
-- HelpDesk — Script para PRODUCTIVO (SQL Server)
-- Fecha: 2026-10-05
-- Descripción: Tipo de ticket dinámico (falla técnica / GPS) +
--              catálogo de vehículos.
--
-- ⚠️ EJECUTAR SOLO TRAS APROBACIÓN. La migración de Laravel
--    (2026_10_05_000001) ya aplica esto en la BD de pruebas MySQL.
-- ============================================================

-- 1. Catálogo de vehículos/unidades
IF OBJECT_ID('dbo.tblVehiculo', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.tblVehiculo (
        id_Vehiculo   INT IDENTITY(1,1) NOT NULL CONSTRAINT PK_tblVehiculo PRIMARY KEY,
        numeroUnidad  VARCHAR(50)  NOT NULL,
        tipoVehiculo  VARCHAR(30)  NOT NULL,
        fechaAlta     DATETIME     NOT NULL,
        activo        TINYINT      NOT NULL,
        CONSTRAINT UQ_tblVehiculo_numeroUnidad UNIQUE (numeroUnidad)
    );
END
GO

-- 2. Columnas nuevas en tblTicket
IF COL_LENGTH('dbo.tblTicket', 'tipoTicket') IS NULL
    ALTER TABLE dbo.tblTicket ADD tipoTicket VARCHAR(20) NOT NULL CONSTRAINT DF_tblTicket_tipoTicket DEFAULT ('falla_tecnica');
GO

IF COL_LENGTH('dbo.tblTicket', 'vehiculos') IS NULL
    ALTER TABLE dbo.tblTicket ADD vehiculos VARCHAR(255) NULL;
GO

IF COL_LENGTH('dbo.tblTicket', 'problemaGps') IS NULL
    ALTER TABLE dbo.tblTicket ADD problemaGps VARCHAR(200) NULL;
GO

-- 3. Índice para búsquedas por tipo de ticket
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_tblTicket_tipoTicket' AND object_id = OBJECT_ID('dbo.tblTicket'))
    CREATE INDEX IX_tblTicket_tipoTicket ON dbo.tblTicket (tipoTicket);
GO
