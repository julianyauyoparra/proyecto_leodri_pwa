-- Añade galería de 6 vistas por color (ejecutar una vez)
USE solucion_db_leodri;

ALTER TABLE producto_colores
    ADD COLUMN IF NOT EXISTS imagenes JSON NULL AFTER imagen;
