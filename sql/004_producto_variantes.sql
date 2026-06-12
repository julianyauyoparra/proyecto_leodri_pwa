-- Stock por color + talla (SKU: REF-C1-36, etc.)

USE solucion_db_leodri;

CREATE TABLE IF NOT EXISTS producto_variantes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    producto_id INT UNSIGNED NOT NULL,
    color_codigo VARCHAR(20) NOT NULL,
    talla_numero VARCHAR(10) NOT NULL,
    disponible TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_variantes_producto
        FOREIGN KEY (producto_id) REFERENCES productos (id)
        ON DELETE CASCADE,
    UNIQUE KEY uk_variante (producto_id, color_codigo, talla_numero),
    INDEX idx_variantes_producto (producto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
