-- Permitir eliminar productos conservando pedidos históricos (FK → SET NULL).
-- La migración PHP db_migrar_pedidos_fk_set_null() aplica esto automáticamente.

USE leodri;

ALTER TABLE pedidos DROP FOREIGN KEY fk_pedidos_variante;
ALTER TABLE pedidos DROP FOREIGN KEY fk_pedidos_producto;
ALTER TABLE pedidos DROP FOREIGN KEY fk_pedidos_color;
ALTER TABLE pedidos DROP FOREIGN KEY fk_pedidos_talla;

ALTER TABLE pedidos
    MODIFY inventario_variante_id INT UNSIGNED NULL,
    MODIFY producto_id INT UNSIGNED NULL,
    MODIFY producto_color_id INT UNSIGNED NULL,
    MODIFY producto_talla_id INT UNSIGNED NULL;

ALTER TABLE pedidos
    ADD CONSTRAINT fk_pedidos_variante
        FOREIGN KEY (inventario_variante_id) REFERENCES inventario_variantes (id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_pedidos_producto
        FOREIGN KEY (producto_id) REFERENCES productos (id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_pedidos_color
        FOREIGN KEY (producto_color_id) REFERENCES producto_colores (id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_pedidos_talla
        FOREIGN KEY (producto_talla_id) REFERENCES producto_tallas (id) ON DELETE SET NULL;
