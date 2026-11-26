-- ============================================================
-- HelpDesk -- Script para PRODUCTIVO (SQL Server)
-- Fecha: 2026-11-17
-- Descripción: Evidencias de tickets en Google Drive.
--              Agrega el fileId de Drive a tblTicketEvidencia.
--
-- Ejecutar SOLO TRAS APROBACIÓN. La migración de Laravel
-- (2026_11_17_111319) ya aplica esto en la BD local.
--
-- Notas:
--   * Es idempotente: solo agrega la columna si no existe.
--   * driveFileId es NULL para las evidencias antiguas, que siguen
--     siendo archivos locales en public/evidencias.
-- ============================================================

IF COL_LENGTH('dbo.tblTicketEvidencia', 'driveFileId') IS NULL
    ALTER TABLE dbo.tblTicketEvidencia ADD driveFileId VARCHAR(100) NULL;
GO
