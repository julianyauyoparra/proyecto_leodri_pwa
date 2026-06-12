<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/categorias_tienda.php';

/** @var string $tiendaBase Prefijo de ruta (ej. '' desde raíz PWA) */
$tiendaBase = $tiendaBase ?? '';
$categoriaNavActiva = $categoriaNavActiva ?? CATEGORIA_HOME_DEFAULT;
$categoriasNav = categorias_tienda_nav();
$homeBase = $tiendaBase === '' ? './' : $tiendaBase;
?>
<nav class="tienda-nav" aria-label="Navegación principal">
    <div class="tienda-nav__inner">
        <ul class="tienda-nav__lista">
            <?php foreach ($categoriasNav as $slug => $etiqueta): ?>
                <?php
                $activo = $slug === categoria_normalizar($categoriaNavActiva);
                $href = $homeBase . '?categoria=' . rawurlencode($slug) . '#tienda-productos';
                ?>
                <li class="tienda-nav__item">
                    <a
                        class="tienda-nav__enlace<?= $activo ? ' is-active' : '' ?>"
                        href="<?= h($href) ?>"
                        <?= $activo ? 'aria-current="page"' : '' ?>
                    ><?= h($etiqueta) ?></a>
                </li>
            <?php endforeach; ?>
            <li class="tienda-nav__item">
                <a class="tienda-nav__enlace" href="<?= h($tiendaBase) ?>admin/login.php">Intranet</a>
            </li>
        </ul>
    </div>
</nav>
