<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/admin_auth.php';
require_once dirname(__DIR__) . '/includes/importacion_masiva.php';
require_once dirname(__DIR__) . '/includes/admin_layout.php';

admin_requerir_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !admin_verificar_csrf($_POST['csrf'] ?? null)) {
    header('Location: carga_masiva.php?error=csrf');
    exit;
}

$filasJson = (string) ($_POST['filas_json'] ?? '');
$filasDatos = json_decode($filasJson, true);
if (!is_array($filasDatos) || $filasDatos === []) {
    header('Location: carga_masiva.php?error=vacio');
    exit;
}

$filas = [];
foreach ($filasDatos as $item) {
    if (!is_array($item)) {
        continue;
    }
    $filas[] = importacion_normalizar_fila([
        $item['marca'] ?? '',
        $item['nombre'] ?? '',
        $item['precio'] ?? $item['precio_raw'] ?? '',
        $item['tallas'] ?? $item['tallas_raw'] ?? '',
        $item['stock'] ?? $item['stock_raw'] ?? '',
        $item['color'] ?? '',
        $item['notas'] ?? '',
    ]);
}

$resultado = importacion_ejecutar($filas);

$_SESSION['importacion_resultado'] = $resultado;
header('Location: carga_masiva.php?importado=1');
exit;
