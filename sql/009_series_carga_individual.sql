-- Series comerciales, curvas, público objetivo y guías por producto

USE solucion_db_leodri;

CREATE TABLE IF NOT EXISTS producto_series (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(40) NOT NULL,
    codigo_corto VARCHAR(10) NOT NULL,
    nombre VARCHAR(120) NOT NULL,
    talla_min TINYINT UNSIGNED NOT NULL,
    talla_max TINYINT UNSIGNED NOT NULL,
    segmento VARCHAR(120) NOT NULL DEFAULT '',
    curva_docena JSON NOT NULL,
    tallas_especiales JSON NOT NULL,
    UNIQUE KEY uk_series_slug (slug),
    UNIQUE KEY uk_series_codigo (codigo_corto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS preajustes_curvas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    serie_id INT UNSIGNED NOT NULL,
    tipo ENUM('media_docena_central', 'media_docena_chica', 'media_docena_grande') NOT NULL,
    nombre_curva VARCHAR(120) NOT NULL,
    cantidad_total TINYINT UNSIGNED NOT NULL DEFAULT 6,
    distribucion JSON NOT NULL,
    CONSTRAINT fk_curvas_serie
        FOREIGN KEY (serie_id) REFERENCES producto_series (id)
        ON DELETE CASCADE,
    UNIQUE KEY uk_curva_serie_tipo (serie_id, tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS producto_publicos (
    codigo VARCHAR(30) NOT NULL PRIMARY KEY,
    etiqueta VARCHAR(80) NOT NULL,
    genero ENUM('masculino', 'femenino', 'unisex') NOT NULL DEFAULT 'unisex',
    orden TINYINT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS producto_guias_tallas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    producto_id INT UNSIGNED NOT NULL,
    serie_slug VARCHAR(40) NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    filas JSON NOT NULL,
    imagen_instruccion VARCHAR(500) NOT NULL DEFAULT '',
    archivo_pdf VARCHAR(500) NOT NULL DEFAULT '',
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_guia_producto
        FOREIGN KEY (producto_id) REFERENCES productos (id)
        ON DELETE CASCADE,
    UNIQUE KEY uk_guia_producto_serie (producto_id, serie_slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE productos
    ADD COLUMN IF NOT EXISTS modelo VARCHAR(200) NOT NULL DEFAULT '' AFTER nombre,
    ADD COLUMN IF NOT EXISTS tipo VARCHAR(100) NOT NULL DEFAULT '' AFTER modelo,
    ADD COLUMN IF NOT EXISTS publico VARCHAR(30) NOT NULL DEFAULT 'unisex' AFTER categoria;
