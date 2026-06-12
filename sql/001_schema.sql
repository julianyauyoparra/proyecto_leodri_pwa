-- LEODRI.pe V2 — Esquema de catálogo
-- Ejecutar vía install/setup.php o manualmente en MySQL (XAMPP)

CREATE DATABASE IF NOT EXISTS solucion_db_leodri
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE solucion_db_leodri;

CREATE TABLE IF NOT EXISTS productos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    marca VARCHAR(100) NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT NOT NULL,
    bullets JSON NOT NULL,
    tags JSON NOT NULL,
    precio DECIMAL(10, 2) NOT NULL DEFAULT 0,
    color_default VARCHAR(20) NOT NULL DEFAULT '',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    orden INT NOT NULL DEFAULT 0,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_productos_activo_orden (activo, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS producto_colores (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    producto_id INT UNSIGNED NOT NULL,
    codigo VARCHAR(20) NOT NULL,
    etiqueta VARCHAR(100) NOT NULL,
    imagen VARCHAR(500) NOT NULL,
    imagenes JSON NULL,
    alt VARCHAR(300) NOT NULL DEFAULT '',
    sku_base VARCHAR(120) NOT NULL,
    sku_sin_talla VARCHAR(120) NOT NULL,
    orden INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_colores_producto
        FOREIGN KEY (producto_id) REFERENCES productos (id)
        ON DELETE CASCADE,
    INDEX idx_colores_producto (producto_id, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS producto_tallas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    producto_id INT UNSIGNED NOT NULL,
    numero VARCHAR(10) NOT NULL,
    disponible TINYINT(1) NOT NULL DEFAULT 1,
    orden INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_tallas_producto
        FOREIGN KEY (producto_id) REFERENCES productos (id)
        ON DELETE CASCADE,
    INDEX idx_tallas_producto (producto_id, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS producto_beneficios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    producto_id INT UNSIGNED NOT NULL,
    icono VARCHAR(20) NOT NULL DEFAULT 'check',
    titulo VARCHAR(200) NOT NULL,
    texto TEXT NOT NULL,
    orden INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_beneficios_producto
        FOREIGN KEY (producto_id) REFERENCES productos (id)
        ON DELETE CASCADE,
    INDEX idx_beneficios_producto (producto_id, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(60) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
