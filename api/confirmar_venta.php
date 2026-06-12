<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once dirname(__DIR__) . '/includes/api_agente.php';
require_once dirname(__DIR__) . '/includes/repositorio_productos.php';

$input = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$clave = api_agente_obtener_clave_request($input);
if (!api_agente_verificar_clave($clave)) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autorizado'], JSON_UNESCAPED_UNICODE);
    exit;
}

$sku = trim((string) ($input['sku'] ?? ''));
$productoId = (int) ($input['producto_id'] ?? 0);
$color = trim((string) ($input['color'] ?? ''));
$talla = trim((string) ($input['talla'] ?? ''));

try {
    if ($sku !== '') {
        $resultado = producto_variante_confirmar_venta_por_sku($sku);
    } elseif ($productoId > 0 && $color !== '' && $talla !== '') {
        $resultado = producto_variante_marcar_vendida($productoId, $color, $talla);
        if (!empty($resultado['ok'])) {
            $resultado['producto_id'] = $productoId;
            $resultado['color'] = $color;
            $resultado['talla'] = $talla;
        }
    } else {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Envía sku o producto_id + color + talla'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (empty($resultado['ok'])) {
        http_response_code(!empty($resultado['agotado']) ? 409 : 400);
    }

    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error al confirmar la venta'], JSON_UNESCAPED_UNICODE);
}
