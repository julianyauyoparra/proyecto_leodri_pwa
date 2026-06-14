-- Eliminar columna tags (etiquetas libres no usadas en tienda).
-- La migración PHP db_migrar_quitar_tags() aplica esto automáticamente.

USE solucion_db_leodri;

ALTER TABLE productos DROP COLUMN IF EXISTS tags;
