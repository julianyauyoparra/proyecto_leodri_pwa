-- Precio tachado, descuento y serie de tallas por producto

ALTER TABLE productos
    ADD COLUMN IF NOT EXISTS precio_anterior DECIMAL(10, 2) NOT NULL DEFAULT 0 AFTER precio,
    ADD COLUMN IF NOT EXISTS aplicar_descuento TINYINT(1) NOT NULL DEFAULT 1 AFTER precio_anterior,
    ADD COLUMN IF NOT EXISTS serie VARCHAR(30) NOT NULL DEFAULT 'escolar' AFTER aplicar_descuento;

UPDATE productos
SET precio_anterior = ROUND(precio * 1.27, 2)
WHERE precio > 0 AND (precio_anterior IS NULL OR precio_anterior = 0);
