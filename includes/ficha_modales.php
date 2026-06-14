<?php
declare(strict_types=1);
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
        </div>
    </div>
</div>

<div
    class="ficha-guia"
    id="ficha-guia"
    role="dialog"
    aria-modal="true"
    aria-labelledby="ficha-guia-titulo"
    aria-hidden="true"
>
    <div class="ficha-guia__backdrop" data-guia-cerrar></div>
    <div class="ficha-guia__sheet">
        <header class="ficha-guia__cabecera">
            <h2 class="ficha-guia__titulo" id="ficha-guia-titulo">Guía de tallas</h2>
            <button type="button" class="ficha-guia__cerrar" aria-label="Cerrar guía de tallas">&times;</button>
        </header>
        <div class="ficha-guia__cuerpo" data-guia-contenido></div>
    </div>
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
