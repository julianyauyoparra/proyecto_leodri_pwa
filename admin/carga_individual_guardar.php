<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/admin_auth.php';
require_once dirname(__DIR__) . '/includes/carga_individual.php';
require_once dirname(__DIR__) . '/includes/repositorio_guias.php';
require_once dirname(__DIR__) . '/includes/importacion_masiva.php';
require_once dirname(__DIR__) . '/includes/repositorio_productos.php';

admin_requerir_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !admin_verificar_csrf($_POST['csrf'] ?? null)) {
    header('Location: carga_individual.php');
    exit;
}

$borrador = $_SESSION['admin_carga_individual'] ?? null;
if (!is_array($borrador) || empty($borrador['token'])) {
    $_SESSION['admin_errores'] = ['No hay una carga pendiente. Suba las imágenes de nuevo.'];
    header('Location: carga_individual.php');
    exit;
}

$descripcion = trim((string) ($_POST['descripcion'] ?? ''));
if ($descripcion === '') {
    $_SESSION['admin_errores'] = ['Los detalles del producto son obligatorios.'];
    header('Location: carga_individual.php?paso=validacion');
    exit;
}

$validacionPdf = guia_validar_pdf_upload($_FILES['guia_pdf'] ?? []);
if (!$validacionPdf['ok']) {
    $_SESSION['admin_errores'] = [(string) $validacionPdf['error']];
    header('Location: carga_individual.php?paso=validacion');
    exit;
}

$distribucion = $borrador['distribucion'];
$cantidadesPost = $_POST['cantidad'] ?? [];
foreach ($distribucion as $talla => $defecto) {
    $distribucion[(string) $talla] = max(0, (int) ($cantidadesPost[$talla] ?? $cantidadesPost[(string) $talla] ?? $defecto));
}

$tallasExtra = [];
$numsEsp = $_POST['especial_numero'] ?? [];
$cantEsp = $_POST['especial_cantidad'] ?? [];
foreach ($numsEsp as $i => $num) {
    $num = trim((string) $num);
    $cant = max(0, (int) ($cantEsp[$i] ?? 0));
    if ($num === '' || $cant <= 0) {
        continue;
    }
    $tallasExtra[] = ['numero' => $num, 'cantidad' => $cant];
}

try {
    $duplicado = carga_buscar_producto_duplicado($borrador['datos']);
    if ($duplicado !== null) {
        $_SESSION['admin_errores'] = [carga_mensaje_producto_duplicado($duplicado)];
        $_SESSION['admin_carga_duplicado_id'] = (int) $duplicado['id'];
        header('Location: carga_individual.php?paso=validacion');
        exit;
    }

    $preparado = carga_construir_producto(
        $borrador['datos'],
        $distribucion,
        $borrador['archivos_tmp'],
        (string) $borrador['publico'],
        $descripcion,
        $tallasExtra
    );

    $payload = $preparado['payload'];
    $productoId = producto_guardar(null, $payload);

    $imagenes = carga_mover_imagenes_producto($productoId, $preparado['imagenes_tmp']);
    $imagenJson = json_encode($imagenes, JSON_UNESCAPED_UNICODE);
    $imagenThumb = imagen_thumbnail_desde_vistas($imagenes);

    $pdo = db();
    $stmtColor = $pdo->prepare(
        'SELECT id FROM producto_colores WHERE producto_id = :id ORDER BY id ASC LIMIT 1'
    );
    $stmtColor->execute(['id' => $productoId]);
    $colorId = (int) $stmtColor->fetchColumn();
    if ($colorId > 0) {
        $pdo->prepare(
            'UPDATE producto_colores SET imagen = :imagen, imagenes = :imagenes WHERE id = :cid'
        )->execute([
            'imagen' => $imagenThumb,
            'imagenes' => $imagenJson,
            'cid' => $colorId,
        ]);
    }

    inventario_aplicar_stock_inicial($productoId, $payload['colores'][0]['codigo'], $preparado['stock_mapa']);

    $pdfGuardado = guia_guardar_pdf_producto($productoId, $_FILES['guia_pdf']);
    if (!$pdfGuardado['ok']) {
        throw new RuntimeException((string) $pdfGuardado['error']);
    }

    $tituloGuia = 'Guía de tallas — ' . ($borrador['datos']['serie_nombre'] ?? '');
    guia_guardar(
        $productoId,
        (string) $preparado['serie_slug'],
        $tituloGuia,
        (string) $pdfGuardado['ruta']
    );

    carga_limpiar_tmp((string) $borrador['token']);
    unset($_SESSION['admin_carga_individual']);

    $_SESSION['admin_exito'] = 'Producto cargado correctamente desde carga individual.';
    header('Location: productos.php?cargado=1');
    exit;
} catch (Throwable $e) {
    $_SESSION['admin_errores'] = ['Error al guardar: ' . $e->getMessage()];
    header('Location: carga_individual.php?paso=validacion');
    exit;
}
