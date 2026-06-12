<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/admin_auth.php';
require_once dirname(__DIR__) . '/includes/repositorio_productos.php';
require_once dirname(__DIR__) . '/includes/admin_layout.php';

admin_requerir_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$esNuevo = $id <= 0;
$errores = $_SESSION['admin_errores'] ?? [];
unset($_SESSION['admin_errores']);

if ($esNuevo) {
    $producto = [
        'id' => '',
        'marca' => '',
        'nombre' => '',
        'descripcion' => '',
        'bullets' => [],
        'tags' => [],
        'precio' => 0,
        'precio_anterior' => 0,
        'aplicar_descuento' => true,
        'serie' => 'escolar',
        'colores' => [
            [
                'codigo_inventario' => '',
                'imagen' => '',
                'imagenes' => [],
            ],
        ],
        'tallas' => [
            ['numero' => '', 'disponible' => true],
        ],
        'beneficios' => [
            ['icono' => 'check', 'titulo' => '', 'texto' => ''],
        ],
    ];
    $titulo = 'Nuevo producto';
} else {
    $producto = producto_obtener_admin($id);
    if ($producto === null) {
        header('Location: productos.php');
        exit;
    }
    $titulo = 'Editar producto';
}

if (!empty($_SESSION['admin_form_old'])) {
    $producto = array_merge($producto, $_SESSION['admin_form_old']);
    unset($_SESSION['admin_form_old']);
}

admin_layout_inicio($titulo);
?>
<p class="admin-subnav">
    <a href="index.php">&larr; Dashboard</a>
    ·
    <a href="productos.php">Ver productos</a>
</p>
<?php if ($errores !== []): ?>
    <div class="admin-alerta admin-alerta--error">
        <ul style="margin:0;padding-left:18px;">
            <?php foreach ($errores as $err): ?>
                <li><?= h($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php require dirname(__DIR__) . '/includes/admin_form_producto.php'; ?>

<?php
admin_layout_fin();
