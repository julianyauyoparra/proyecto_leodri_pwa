<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin_auth.php';
require_once dirname(__DIR__) . '/includes/repositorio_productos.php';
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

try {
    db_migrar_imagenes();
    db_migrar_variantes();
    db_migrar_precio_serie();
    db_migrar_categoria();
    inventario_sincronizar_productos_pendientes();

    if ($esNuevo) {
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

    header('Location: productos.php?guardado=1');
    exit;
} catch (Throwable $e) {
    $_SESSION['admin_errores'] = ['Error al guardar: ' . $e->getMessage()];
    $_SESSION['admin_form_old'] = $datos;
    $redirect = $id ? 'producto.php?id=' . $id : 'producto.php';
    header('Location: ' . $redirect);
    exit;
}
