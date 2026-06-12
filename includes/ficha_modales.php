<?php
declare(strict_types=1);

require_once __DIR__ . '/series_tallas.php';
?>
<div
    class="ficha-detalles"
    id="ficha-detalles"
    role="dialog"
    aria-modal="true"
    aria-labelledby="ficha-detalles-titulo"
    aria-hidden="true"
>
    <div class="ficha-detalles__backdrop" data-detalles-cerrar></div>
    <div class="ficha-detalles__sheet">
        <header class="ficha-detalles__cabecera">
            <h2 class="ficha-detalles__titulo" id="ficha-detalles-titulo">Detalles del producto</h2>
            <button type="button" class="ficha-detalles__cerrar" aria-label="Cerrar detalles">&times;</button>
        </header>
        <div class="ficha-detalles__cuerpo">
            <div class="ficha-detalles__contenido" data-detalles-contenido></div>
            <section class="ficha-detalles__guia" data-detalles-guia-wrap>
                <h3 class="ficha-detalles__subtitulo" data-detalles-guia-titulo>Guía de tallas</h3>
                <div data-detalles-guia></div>
            </section>
        </div>
    </div>
</div>

<div id="ficha-guias-serie" hidden>
    <?php foreach (series_opciones() as $clave => $etiqueta): ?>
        <div class="ficha-guia-serie" data-serie="<?= h($clave) ?>" data-titulo="<?= h(series_guia_titulo($clave)) ?>">
            <?= series_render_guia_html($clave) ?>
        </div>
    <?php endforeach; ?>
</div>

<div
    class="ficha-lightbox"
    id="ficha-lightbox"
    role="dialog"
    aria-modal="true"
    aria-label="Vista ampliada del producto"
    aria-hidden="true"
>
    <div class="ficha-lightbox__backdrop" data-lightbox-cerrar></div>
    <button type="button" class="ficha-lightbox__cerrar" aria-label="Cerrar imagen">&times;</button>
    <div class="ficha-lightbox__viewport">
        <button
            type="button"
            class="ficha-lightbox__nav ficha-lightbox__nav--prev"
            aria-label="Imagen anterior"
            hidden
        >&lsaquo;</button>
        <figure class="ficha-lightbox__figure">
            <div class="ficha-lightbox__zoom-wrap">
                <img class="ficha-lightbox__img" src="" alt="">
            </div>
        </figure>
        <button
            type="button"
            class="ficha-lightbox__nav ficha-lightbox__nav--next"
            aria-label="Imagen siguiente"
            hidden
        >&rsaquo;</button>
    </div>
    <p class="ficha-lightbox__contador" aria-live="polite" hidden></p>
</div>
