-- Elimina columnas legacy de visibilidad/orden del catálogo (sustituido por home).
-- Ejecutar una vez en prod/local si la tabla aún las tiene.

ALTER TABLE productos DROP INDEX idx_productos_activo_orden;
ALTER TABLE productos DROP COLUMN activo;
ALTER TABLE productos DROP COLUMN orden;
