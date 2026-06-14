-- Guía de tallas como PDF por producto (reemplaza filas manuales en admin).
-- La migración PHP db_migrar_guia_pdf() aplica esto automáticamente.

USE solucion_db_leodri;

ALTER TABLE producto_guias_tallas
    ADD COLUMN IF NOT EXISTS archivo_pdf VARCHAR(500) NOT NULL DEFAULT '' AFTER imagen_instruccion;
