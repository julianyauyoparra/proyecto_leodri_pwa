<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/repositorio_productos.php';

$productoId = isset($_GET['producto_id']) ? (int) $_GET['producto_id'] : 0;

if ($productoId <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'producto_id requerido'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $variantes = producto_cargar_variantes($productoId);
    $colores = [];

    foreach ($variantes as $codigo => $tallas) {
        $mapa = [];
        foreach ($tallas as $numero => $disponible) {
            $mapa[(string) $numero] = (bool) $disponible;
        }
        $colores[$codigo] = $mapa;
    }

    echo json_encode([
        'ok' => true,
        'producto_id' => $productoId,
        'colores' => $colores,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error al consultar stock'], JSON_UNESCAPED_UNICODE);
}
