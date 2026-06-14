<?php

declare(strict_types=1);



require_once dirname(__DIR__) . '/includes/helpers.php';

require_once dirname(__DIR__) . '/includes/admin_auth.php';

require_once dirname(__DIR__) . '/includes/repositorio_productos.php';

require_once dirname(__DIR__) . '/includes/admin_layout.php';



admin_requerir_login();



$productos = productos_listar_admin();

$guardado = isset($_GET['guardado']);

$cargado = isset($_GET['cargado']);

$eliminado = isset($_GET['eliminado']);

$exito = $_SESSION['admin_exito'] ?? '';

unset($_SESSION['admin_exito']);

$csrf = admin_csrf_token();



admin_layout_inicio('Productos', true, 'admin-body--productos');

?>

<nav class="admin-subnav admin-subnav--stack" aria-label="Navegación">

    <a href="index.php">&larr; Dashboard</a>

    <a href="carga_masiva.php">Carga masiva</a>

</nav>



<?php if ($guardado): ?>

    <div class="admin-alerta admin-alerta--ok">Producto guardado correctamente.</div>

<?php endif; ?>

<?php if ($cargado || $exito !== ''): ?>

    <div class="admin-alerta admin-alerta--ok"><?= h($exito !== '' ? $exito : 'Producto cargado correctamente.') ?></div>

<?php endif; ?>

<?php if ($eliminado): ?>

    <div class="admin-alerta admin-alerta--ok">Producto eliminado.</div>

<?php endif; ?>



<div class="admin-card admin-productos-intro">

    <h2 class="admin-productos-intro__titulo">Productos cargados (<?= count($productos) ?>)</h2>

    <p class="admin-hint admin-productos-intro__texto">

        En la tienda solo se listan productos con foto. Si un producto no tiene imagen, usa <strong>Editar</strong> para subir las fotos.

    </p>

</div>



<?php if ($productos === []): ?>

    <div class="admin-card admin-productos-vacio">

        <p>No hay productos todavía.</p>

        <a href="carga_individual.php" class="admin-btn admin-btn--primario admin-btn--bloque">Cargar primer producto</a>

        <a href="carga_masiva.php" class="admin-btn admin-btn--secundario admin-btn--bloque">Carga masiva</a>

    </div>

<?php else: ?>

    <div class="admin-productos-lista" role="list">

        <?php foreach ($productos as $p):

            $id = (int) $p['id'];

            $tieneFoto = !empty($p['tiene_foto']);

            $thumb = (string) ($p['imagen_thumb'] ?? '');

            $urlHome = '../' . producto_url_en_home($id, (string) ($p['categoria'] ?? ''));

            ?>

            <article class="admin-producto-item" role="listitem">

                <?php if ($thumb !== ''): ?>

                    <img

                        class="admin-producto-item__thumb"

                        src="../<?= h($thumb) ?>"

                        alt=""

                        width="72"

                        height="72"

                        loading="lazy"

                    >

                <?php else: ?>

                    <span class="admin-producto-item__thumb admin-producto-item__thumb--vacio" aria-hidden="true">

                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">

                            <rect x="3" y="3" width="18" height="18" rx="2"></rect>

                            <circle cx="8.5" cy="8.5" r="1.5"></circle>

                            <path d="M21 15l-5-5L5 21"></path>

                        </svg>

                    </span>

                <?php endif; ?>



                <div class="admin-producto-item__body">

                    <p class="admin-producto-item__meta">ID <?= h((string) $id) ?></p>

                    <h3 class="admin-producto-item__nombre"><?= h((string) ($p['titulo_tienda'] ?? $p['nombre'])) ?></h3>

                </div>



                <div class="admin-producto-item__acciones">

                    <?php if ($tieneFoto): ?>

                        <a

                            href="<?= h($urlHome) ?>"

                            class="admin-icon-btn"

                            target="_blank"

                            rel="noopener"

                            aria-label="Ver en tienda"

                            title="Ver en tienda"

                        >

                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">

                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"></path>

                                <circle cx="12" cy="12" r="3"></circle>

                            </svg>

                        </a>

                    <?php else: ?>

                        <span class="admin-icon-btn is-disabled" aria-hidden="true" title="Sin foto — no aparece en la tienda">

                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">

                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"></path>

                                <circle cx="12" cy="12" r="3"></circle>

                            </svg>

                        </span>

                    <?php endif; ?>



                    <a

                        href="producto.php?id=<?= $id ?>"

                        class="admin-icon-btn"

                        aria-label="Editar producto"

                        title="Editar"

                    >

                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">

                            <path d="M12 20h9"></path>

                            <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"></path>

                        </svg>

                    </a>



                    <form

                        method="post"

                        action="eliminar.php"

                        class="admin-producto-item__eliminar"

                        onsubmit="return confirm('¿Eliminar este producto? Esta acción no se puede deshacer.');"

                    >

                        <input type="hidden" name="csrf" value="<?= h($csrf) ?>">

                        <input type="hidden" name="id" value="<?= $id ?>">

                        <button type="submit" class="admin-icon-btn admin-icon-btn--peligro" aria-label="Eliminar producto" title="Eliminar">

                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">

                                <polyline points="3 6 5 6 21 6"></polyline>

                                <path d="M19 6l-1 14H6L5 6"></path>

                                <path d="M10 11v6"></path>

                                <path d="M14 11v6"></path>

                                <path d="M9 6V4h6v2"></path>

                            </svg>

                        </button>

                    </form>

                </div>

            </article>

        <?php endforeach; ?>

    </div>

<?php endif; ?>



<a href="carga_individual.php" class="admin-productos-nuevo admin-btn admin-btn--primario admin-btn--bloque">

    + Nuevo producto

</a>

<?php

admin_layout_fin();


