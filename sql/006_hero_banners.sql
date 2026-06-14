-- Banners del carrusel hero (home)
CREATE TABLE IF NOT EXISTS hero_banners (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    imagen VARCHAR(500) NOT NULL,
    ancho INT UNSIGNED NOT NULL DEFAULT 0,
    alto INT UNSIGNED NOT NULL DEFAULT 0,
    alt VARCHAR(255) NOT NULL DEFAULT '',
    url_destino VARCHAR(500) NOT NULL DEFAULT 'home.php#tienda-productos',
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_hero_banners_creado (creado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
