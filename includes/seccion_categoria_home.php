<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/categorias_tienda.php';

/** @var string $categoriaActiva */
/** @var list<array<string, mixed>> $productosHome */
$categoriaActiva = $categoriaActiva ?? CATEGORIA_HOME_DEFAULT;
$productosHome = $productosHome ?? [];
$tituloSeccion = categoria_etiqueta($categoriaActiva);
?>
<section
    id="tienda-productos"
    class="home-seccion"
    aria-labelledby="home-seccion-titulo"
    data-categoria="<?= h($categoriaActiva) ?>"
>
    <h2 id="home-seccion-titulo" class="home-seccion__titulo"><?= h($tituloSeccion) ?></h2>

    <?php if ($productosHome === []): ?>
        <p class="home-seccion__vacio">No hay productos en <?= h($tituloSeccion) ?> por ahora.</p>
    <?php else: ?>
        <div class="home-catalogo" role="list">
            <?php foreach ($productosHome as $producto): ?>
                <?php require __DIR__ . '/ficha_home.php'; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
