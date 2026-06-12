<?php
declare(strict_types=1);

require_once __DIR__ . '/imagenes_producto.php';
require_once __DIR__ . '/series_tallas.php';
require_once __DIR__ . '/catalogo.php';

/** @var array $producto */
$colorPreviewRaw = color_por_defecto($producto);
$colorPreview = $colorPreviewRaw ? imagenes_normalizar_color($colorPreviewRaw) : null;
if ($colorPreview === null) {
    return;
}

$marca = $producto['marca'] ?? '';
$nombre = $producto['nombre'] ?? '';
$precio = (float) ($producto['precio'] ?? 0);
$precioAnterior = (float) ($producto['precio_anterior'] ?? 0);
$aplicarDescuento = !empty($producto['aplicar_descuento']);
$descuentoPct = (int) ($producto['descuento_pct'] ?? producto_descuento_porcentaje($precio, $precioAnterior));
$serie = series_normalizar((string) ($producto['serie'] ?? 'escolar'));
$heroAlt = trim($marca . ' ' . $nombre);
$imagenPreview = $colorPreview['imagen'] ?? $colorPreview['imagenes']['derecha'] ?? '';
?>
<article
    class="ficha ficha--home"
    data-producto-id="<?= h((string) ($producto['id'] ?? '')) ?>"
    data-color-default="<?= h($producto['color_default'] ?? '') ?>"
    data-serie="<?= h($serie) ?>"
    aria-label="Producto <?= h($nombre) ?>"
>
    <div class="ficha__card ficha__card--home">

        <section class="ficha-galeria ficha-galeria--home" aria-label="Imagen del producto">
            <?php if ($aplicarDescuento && $descuentoPct > 0): ?>
                <span class="ficha-descuento ficha-descuento--home">-<?= $descuentoPct ?>%</span>
            <?php endif; ?>

            <figure class="ficha-galeria__hero ficha-galeria__hero--home" aria-label="Opciones de imagen del producto">
                <img
                    class="ficha-galeria__hero-img"
                    src="<?= h($imagenPreview) ?>"
                    alt="<?= h($heroAlt) ?>"
                    width="420"
                    height="420"
                >
                <div class="ficha-hero-menu" hidden aria-hidden="true">
                    <button type="button" class="ficha-hero-menu__opcion" data-hero-zoom>
                        <span class="ficha-hero-menu__icono" aria-hidden="true">
                            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg>
                        </span>
                        Escalar Imagen
                    </button>
                    <button type="button" class="ficha-hero-menu__opcion" data-hero-detalles>
                        <span class="ficha-hero-menu__icono" aria-hidden="true">
                            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
                        </span>
                        Detalles Producto
                    </button>
                </div>
            </figure>
        </section>

        <div class="ficha-precios ficha-precios--home" aria-label="Precios">
            <p class="ficha-precios__actual"><?= h(formatear_precio($precio)) ?></p>
            <?php if ($aplicarDescuento && $precioAnterior > $precio): ?>
                <p class="ficha-precios__anterior"><?= h(formatear_precio($precioAnterior)) ?></p>
            <?php endif; ?>
        </div>

        <section class="ficha-tallas ficha-tallas--home" aria-label="Selección de talla">
            <div class="ficha-tallas__grid" role="radiogroup" aria-label="Tallas disponibles">
                <?php
                $tallasDisponiblesPreview = $colorPreview['tallas_disponibles'] ?? [];
                foreach ($producto['tallas'] as $talla):
                    $numeroTalla = (string) ($talla['numero'] ?? '');
                    $disponible = array_key_exists($numeroTalla, $tallasDisponiblesPreview)
                        ? !empty($tallasDisponiblesPreview[$numeroTalla])
                        : !empty($talla['disponible']);
                    ?>
                    <button
                        type="button"
                        class="ficha-talla ficha-talla--home<?= $disponible ? '' : ' is-agotada' ?>"
                        data-talla="<?= h($numeroTalla) ?>"
                        aria-checked="false"
                        <?= $disponible ? '' : 'aria-disabled="true" tabindex="-1"' ?>
                    ><?= h($numeroTalla) ?></button>
                <?php endforeach; ?>
            </div>
        </section>

        <div class="ficha-galeria__thumbs ficha-galeria__thumbs--home" role="listbox" aria-label="Colores disponibles">
            <?php foreach ($producto['colores'] as $idxColor => $color):
                $color = imagenes_normalizar_color($color);
                $thumbUrl = imagen_thumbnail($color);
                $imagenesJson = htmlspecialchars(json_encode($color['imagenes'], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                $tallasDispJson = htmlspecialchars(
                    json_encode($color['tallas_disponibles'] ?? [], JSON_UNESCAPED_UNICODE),
                    ENT_QUOTES,
                    'UTF-8'
                );
                ?>
                <button
                    type="button"
                    class="ficha-thumb ficha-thumb--home"
                    role="option"
                    aria-selected="false"
                    aria-label="Color <?= (int) $idxColor + 1 ?>"
                    data-color="<?= h($color['codigo'] ?? '') ?>"
                    data-sku-base="<?= h($color['sku_base'] ?? '') ?>"
                    data-sku-sin-talla="<?= h($color['sku_sin_talla'] ?? '') ?>"
                    data-imagen="<?= h($thumbUrl) ?>"
                    data-imagenes="<?= $imagenesJson ?>"
                    data-tallas-disponibles="<?= $tallasDispJson ?>"
                    data-alt="<?= h($color['alt'] ?: $heroAlt) ?>"
                >
                    <img src="<?= h($thumbUrl) ?>" alt="" width="56" height="56">
                </button>
            <?php endforeach; ?>
        </div>

        <div class="ficha-home-info">
            <p class="ficha-home-info__marca"><?= h($marca) ?></p>
            <h3 class="ficha-home-info__nombre"><?= h($nombre) ?></h3>
            <span
                class="ficha-tag ficha-tag--sku"
                data-tag="sku"
                data-sku-base="<?= h($colorPreview['sku_base'] ?? '') ?>"
                data-sku-sin-talla="<?= h($colorPreview['sku_sin_talla'] ?? '') ?>"
                hidden
            ><?= h($colorPreview['sku_sin_talla'] ?? '') ?></span>
        </div>

        <footer class="ficha-accion ficha-accion--home">
            <div class="ficha-accion__cta-wrap" hidden>
                <button
                    type="button"
                    class="ficha-accion__cta ficha-accion__cta--home is-activo"
                    aria-label="Lo quiero"
                >
                    LO QUIERO
                </button>
            </div>
            <p class="ficha-accion__ayuda ficha-accion__ayuda--home" data-ayuda-cta>
                Selecciona color y talla
            </p>
        </footer>

    </div>

    <template class="ficha-detalles-tpl">
        <p class="ficha-detalles__desc"><?= h($producto['descripcion'] ?? '') ?></p>
        <?php if (!empty($producto['bullets'])): ?>
            <ul class="ficha-detalles__bullets">
                <?php foreach ($producto['bullets'] as $bullet): ?>
                    <li><?= h($bullet) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <?php if (!empty($producto['beneficios'])): ?>
            <section class="ficha-detalles__beneficios">
                <h3 class="ficha-detalles__subtitulo">Beneficios Destacados</h3>
                <ul class="ficha-beneficios__lista">
                    <?php foreach ($producto['beneficios'] as $beneficio): ?>
                        <li class="ficha-beneficio">
                            <span class="ficha-beneficio__icono" aria-hidden="true">
                                <?= icono_beneficio($beneficio['icono'] ?? 'check') ?>
                            </span>
                            <div class="ficha-beneficio__texto">
                                <h4 class="ficha-beneficio__titulo"><?= h($beneficio['titulo'] ?? '') ?></h4>
                                <p><?= $beneficio['texto'] ?? '' ?></p>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>
    </template>

    <div class="ficha-sticky" data-ficha-sticky aria-hidden="true" hidden></div>
</article>
