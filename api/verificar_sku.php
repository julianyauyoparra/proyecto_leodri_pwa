<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/repositorio_productos.php';

$sku = strtoupper(trim((string) ($_GET['sku'] ?? '')));

if ($sku === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'sku requerido'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = db();
    $host = $pdo->query('SELECT @@hostname AS servidor, DATABASE() AS bd')->fetch();

    $stmt = $pdo->prepare(
        'SELECT iv.sku, iv.stock, iv.stock_reservado,
                IF((iv.stock - iv.stock_reservado) > 0, 1, 0) AS disponible_inventario,
                pv.disponible AS disponible_variante
         FROM inventario_variantes iv
         LEFT JOIN producto_colores pc ON pc.id = iv.producto_color_id
         LEFT JOIN producto_tallas pt ON pt.id = iv.producto_talla_id
         LEFT JOIN producto_variantes pv
           ON pv.producto_id = iv.producto_id
          AND pv.color_codigo = pc.codigo
          AND pv.talla_numero = pt.numero
         WHERE iv.sku = :sku
         LIMIT 1'
    );
    $stmt->execute(['sku' => $sku]);
    $fila = $stmt->fetch();

    echo json_encode([
        'ok' => true,
        'servidor' => $host['servidor'] ?? null,
        'bd' => $host['bd'] ?? null,
        'sku' => $sku,
        'variante' => $fila ?: null,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Error al verificar SKU',
        'detalle' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
