<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin_auth.php';
require_once dirname(__DIR__) . '/includes/repositorio_productos.php';
require_once dirname(__DIR__) . '/includes/repositorio_guias.php';
require_once dirname(__DIR__) . '/includes/repositorio_series.php';
require_once dirname(__DIR__) . '/includes/importacion_masiva.php';
require_once dirname(__DIR__) . '/includes/upload_producto.php';
require_once dirname(__DIR__) . '/includes/imagenes_producto.php';

admin_requerir_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !admin_verificar_csrf($_POST['csrf'] ?? null)) {
    header('Location: productos.php');
    exit;
}

$id = isset($_POST['id']) && $_POST['id'] !== '' ? (int) $_POST['id'] : null;
$esNuevo = $id === null;
$datos = admin_parsear_producto_post($_POST);
$guiaPdfAnterior = null;

if (!$esNuevo) {
    $serieAnterior = series_resolver_slug_producto((string) ($datos['serie_anterior'] ?? ''));
    $guiaPdfAnterior = guia_obtener($id, $serieAnterior);
}

try {
    db_migrar_imagenes();
    db_migrar_variantes();
    db_migrar_precio_serie();
    db_migrar_categoria();
    db_migrar_guia_pdf();
    inventario_sincronizar_productos_pendientes();

    if ($esNuevo) {
        $subioPdf = isset($_FILES['guia_pdf']) && (int) ($_FILES['guia_pdf']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        if (!$subioPdf) {
            $_SESSION['admin_errores'] = ['Debe subir el PDF de la guía de tallas.'];
            $_SESSION['admin_form_old'] = $datos;
            header('Location: producto.php');
            exit;
        }
        $id = producto_guardar(null, $datos);
    }

    $datos['colores'] = upload_procesar_colores($id, $_POST, $_FILES, $datos['colores']);

    $errores = admin_validar_producto($datos);
    if ($errores !== []) {
        $_SESSION['admin_errores'] = $errores;
        $_SESSION['admin_form_old'] = $datos;
        header('Location: producto.php?id=' . $id);
        exit;
    }

    producto_guardar($id, $datos);

    if (($datos['color_default'] ?? '') !== '' && ($datos['stock_mapa'] ?? []) !== []) {
        $disponibleMap = $datos['colores'][0]['variantes'] ?? [];
        inventario_aplicar_stock_inicial(
            $id,
            (string) $datos['color_default'],
            $datos['stock_mapa'],
            $disponibleMap
        );
    }

    $serieSlug = series_resolver_slug_producto((string) ($datos['serie'] ?? ''));
    $serieInfo = series_obtener_por_slug($serieSlug);
    $tituloGuia = 'Guía de tallas — ' . ($serieInfo['nombre'] ?? $serieSlug);

    $subioPdfNuevo = isset($_FILES['guia_pdf']) && (int) ($_FILES['guia_pdf']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    if ($subioPdfNuevo) {
        $pdfGuardado = guia_guardar_pdf_producto($id, $_FILES['guia_pdf']);
        if (!$pdfGuardado['ok']) {
            throw new RuntimeException((string) $pdfGuardado['error']);
        }
        guia_guardar($id, $serieSlug, $tituloGuia, (string) $pdfGuardado['ruta']);
    } elseif ($guiaPdfAnterior !== null && trim((string) ($guiaPdfAnterior['archivo_pdf'] ?? '')) !== '') {
        guia_guardar(
            $id,
            $serieSlug,
            $tituloGuia,
            (string) $guiaPdfAnterior['archivo_pdf']
        );
    }

    header('Location: productos.php?guardado=1');
    exit;
} catch (Throwable $e) {
    $_SESSION['admin_errores'] = ['Error al guardar: ' . $e->getMessage()];
    $_SESSION['admin_form_old'] = $datos;
    $redirect = $id ? 'producto.php?id=' . $id : 'producto.php';
    header('Location: ' . $redirect);
    exit;
}
