<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/admin_auth.php';
require_once dirname(__DIR__) . '/includes/repositorio_banners.php';
require_once dirname(__DIR__) . '/includes/admin_layout.php';

admin_requerir_login();

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_verificar_csrf($_POST['csrf'] ?? null)) {
        $error = 'La sesión expiró. Vuelve a intentar.';
    } else {
        try {
            if (empty($_FILES['banner']['tmp_name'])) {
                throw new RuntimeException('Elige una imagen (JPG, PNG o WEBP).');
            }

            $mantenerTodas = !empty($_POST['mantener_todas']);
            $alt = trim((string) ($_POST['alt'] ?? ''));
            $urlDestino = trim((string) ($_POST['url_destino'] ?? 'catalogo.php'));
            $resultado = banners_subir($_FILES['banner'], $mantenerTodas, $alt, $urlDestino);

            if ($resultado['accion'] === 'reemplazado') {
                $mensaje = 'Banner actualizado: se reemplazó el más antiguo. Total visible: ' . $resultado['total'] . '.';
            } else {
                $mensaje = 'Banner agregado. Total visible: ' . $resultado['total'] . ' (máximo ' . BANNERS_MAXIMO . ').';
            }
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

$banners = banners_listar();
$totalBanners = count($banners);

admin_layout_inicio('Agregar banner');
?>
<p class="admin-subnav">
    <a href="index.php">&larr; Dashboard</a>
</p>

<?php if ($mensaje !== ''): ?>
    <div class="admin-alerta admin-alerta--ok"><?= h($mensaje) ?></div>
<?php endif; ?>
<?php if ($error !== ''): ?>
    <div class="admin-alerta admin-alerta--error"><?= h($error) ?></div>
<?php endif; ?>

<div class="admin-card">
    <h2 style="margin:0 0 8px;">Banners del inicio</h2>
    <p class="admin-hint" style="margin:0 0 20px;">
        Por defecto hay 3 banners. Al subir sin marcar «Mantener todas», se reemplaza el banner más antiguo.
        Con «Mantener todas» se agregan hasta un máximo de <?= (int) BANNERS_MAXIMO ?>; si ya hay
        <?= (int) BANNERS_MAXIMO ?>, el nuevo reemplaza al más antiguo.
        Recomendado: imágenes de al menos <strong>1600 px de ancho</strong> (ideal 1920 px) para buena calidad en pantallas grandes.
    </p>

    <form method="post" enctype="multipart/form-data" class="admin-banner-form">
        <input type="hidden" name="csrf" value="<?= h(admin_csrf_token()) ?>">

        <label class="admin-banner-form__campo">
            <span>Imagen del banner</span>
            <input type="file" name="banner" accept="image/jpeg,image/png,image/webp" required>
        </label>

        <label class="admin-banner-form__campo">
            <span>Texto alternativo (opcional)</span>
            <input type="text" name="alt" maxlength="255" placeholder="Ej.: 2x1 en zapatillas seleccionadas">
        </label>

        <label class="admin-banner-form__campo">
            <span>Enlace al hacer clic (opcional)</span>
            <input type="text" name="url_destino" maxlength="500" value="catalogo.php" placeholder="catalogo.php">
        </label>

        <label class="admin-banner-form__check">
            <input type="checkbox" name="mantener_todas" value="1">
            <span>Mantener todas (agregar sin quitar las actuales, hasta <?= (int) BANNERS_MAXIMO ?>)</span>
        </label>

        <button type="submit" class="admin-btn admin-btn--primario">Subir banner</button>
    </form>
</div>

<?php if ($banners !== []): ?>
    <div class="admin-card" style="margin-top:20px;">
        <h3 style="margin:0 0 16px;">Banners actuales (<?= (int) $totalBanners ?>)</h3>
        <p class="admin-hint" style="margin:0 0 16px;">Orden del carrusel: del más antiguo al más reciente.</p>
        <div class="admin-banners-grid">
            <?php foreach ($banners as $indice => $banner): ?>
                <article class="admin-banner-card">
                    <img
                        src="../<?= h(ltrim((string) $banner['imagen'], '/')) ?>"
                        alt="<?= h((string) $banner['alt']) ?>"
                        class="admin-banner-card__img"
                        loading="lazy"
                    >
                    <div class="admin-banner-card__meta">
                        <strong>#<?= $indice + 1 ?></strong>
                        <span><?= (int) $banner['ancho'] ?>×<?= (int) $banner['alto'] ?> px</span>
                        <span><?= h((string) $banner['creado_en']) ?></span>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
<?php
admin_layout_fin();
