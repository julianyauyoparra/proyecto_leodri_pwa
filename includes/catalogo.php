<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

function cargar_catalogo(): array
{
    static $cache = null;

    if ($cache !== null) {
        return $cache;
    }

    try {
        require_once __DIR__ . '/db.php';
        require_once __DIR__ . '/imagenes_producto.php';
        db_migrar_imagenes();
        require_once __DIR__ . '/repositorio_productos.php';
        db_migrar_variantes();
        db_migrar_precio_serie();
        db_migrar_categoria();
        inventario_sincronizar_productos_pendientes();
        $cache = productos_listar_catalogo(true);
        return $cache;
    } catch (Throwable $e) {
        return cargar_catalogo_json();
    }
}

function cargar_catalogo_json(): array
{
    $ruta = dirname(__DIR__) . '/data/catalogo.json';

    if (!is_readable($ruta)) {
        return [];
    }

    $contenido = file_get_contents($ruta);
    if ($contenido === false) {
        return [];
    }

    $datos = json_decode($contenido, true);
    if (!is_array($datos) || !isset($datos['productos']) || !is_array($datos['productos'])) {
        return [];
    }

    return $datos['productos'];
}

function color_por_defecto(array $producto): ?array
{
    $colores = $producto['colores'] ?? [];
    if ($colores === []) {
        return null;
    }

    $codigo = $producto['color_default'] ?? '';
    foreach ($colores as $color) {
        if (($color['codigo'] ?? '') === $codigo) {
            return $color;
        }
    }

    return $colores[0];
}
