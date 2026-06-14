-- Quitar beneficios y características destacadas (bullets).
-- La migración PHP (db_migrar_quitar_beneficios_bullets) fusiona datos en descripcion antes del DROP.

USE solucion_db_leodri;

DROP TABLE IF EXISTS producto_beneficios;

ALTER TABLE productos DROP COLUMN IF EXISTS bullets;
