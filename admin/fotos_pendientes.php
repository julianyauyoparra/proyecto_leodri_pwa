<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/admin_auth.php';
require_once dirname(__DIR__) . '/includes/importacion_masiva.php';
require_once dirname(__DIR__) . '/includes/admin_layout.php';

admin_requerir_login();

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_verificar_csrf($_POST['csrf'] ?? null)) {
        $error = 'La sesión expiró. Vuelve a intentar.';
    } else {
        $productoId = (int) ($_POST['producto_id'] ?? 0);
        try {
            if ($productoId <= 0) {
                throw new RuntimeException('Producto no válido.');
            }
            if (empty($_FILES['foto']['tmp_name'])) {
                throw new RuntimeException('Elige una imagen (JPG, PNG o WEBP).');
            }
            producto_subir_foto_principal($productoId, $_FILES['foto']);
            $mensaje = 'Foto guardada correctamente.';
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

$pendientes = productos_listar_sin_foto();

admin_layout_inicio('Completar fotos');
?>
<p class="admin-subnav">
    <a href="index.php">&larr; Dashboard</a>
    ·
    <a href="carga_masiva.php">Carga masiva</a>
</p>

<?php if ($mensaje !== ''): ?>
    <div class="admin-alerta admin-alerta--ok"><?= h($mensaje) ?></div>
<?php endif; ?>
<?php if ($error !== ''): ?>
    <div class="admin-alerta admin-alerta--error"><?= h($error) ?></div>
<?php endif; ?>

<div class="admin-card">
    <h2 style="margin:0 0 8px;">Completar fotos</h2>
    <p class="admin-hint" style="margin:0 0 20px;">
        Sube una foto por producto. Se usará como imagen principal del catálogo.
        Los productos con foto pueden aparecer en la tienda según las reglas del catálogo.
    </p>

    <?php if ($pendientes === []): ?>
        <p class="admin-carga-vacio">Todos los productos tienen foto. <a href="productos.php">Ver productos</a></p>
    <?php else: ?>
        <div class="admin-fotos-grid">
            <?php foreach ($pendientes as $p): ?>
                <article class="admin-foto-card">
                    <div class="admin-foto-card__info">
                        <strong><?= h($p['marca']) ?></strong>
                        <span><?= h($p['nombre']) ?></span>
                        <span class="admin-foto-card__precio"><?= h(formatear_precio((float) $p['precio'])) ?></span>
                    </div>
                    <form method="post" enctype="multipart/form-data" class="admin-foto-card__form">
                        <input type="hidden" name="csrf" value="<?= h(admin_csrf_token()) ?>">
                        <input type="hidden" name="producto_id" value="<?= (int) $p['id'] ?>">
                        <label class="admin-foto-card__label">
                            <span>Elegir foto</span>
                            <input type="file" name="foto" accept="image/jpeg,image/png,image/webp" required>
                        </label>
                        <button type="submit" class="admin-btn admin-btn--primario admin-btn--chico">Subir foto</button>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php
admin_layout_fin();
