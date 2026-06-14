<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin_auth.php';
require_once dirname(__DIR__) . '/includes/repositorio_productos.php';

admin_requerir_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !admin_verificar_csrf($_POST['csrf'] ?? null)) {
    header('Location: productos.php');
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
if ($id > 0) {
    try {
        producto_eliminar($id);
        header('Location: productos.php?eliminado=1');
        exit;
    } catch (Throwable $e) {
        $_SESSION['admin_errores'] = ['No se pudo eliminar el producto: ' . $e->getMessage()];
        header('Location: producto.php?id=' . $id);
        exit;
    }
}

header('Location: productos.php');
exit;
