<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

/** @var string $tiendaBase Prefijo de ruta (ej. '' desde raíz PWA) */
$tiendaBase = $tiendaBase ?? '';

require_once __DIR__ . '/repositorio_banners.php';

$heroBanners = banners_listar_para_tienda();
$heroDestinoDefault = $tiendaBase . 'catalogo.php';

if ($heroBanners === []) {
    return;
}
?>
<section
    class="tienda-hero"
    aria-label="Promociones destacadas"
    data-hero-interval="3000"
>
    <div class="tienda-hero__viewport">
        <div class="tienda-hero__track">
            <?php foreach ($heroBanners as $indice => $banner): ?>
                <?php
                $destinoRel = ltrim((string) ($banner['url_destino'] ?? 'catalogo.php'), '/');
                $destino = $destinoRel === '' ? $heroDestinoDefault : $tiendaBase . $destinoRel;
                $ancho = max(1, (int) ($banner['ancho'] ?? 0));
                $alto = max(1, (int) ($banner['alto'] ?? 0));
                $src = $tiendaBase . ltrim((string) $banner['imagen'], '/');
                ?>
                <article
                    class="tienda-hero__slide<?= $indice === 0 ? ' is-active' : '' ?>"
                    aria-hidden="<?= $indice === 0 ? 'false' : 'true' ?>"
                    data-hero-slide
                >
                    <a class="tienda-hero__enlace" href="<?= h($destino) ?>">
                        <img
                            class="tienda-hero__imagen"
                            src="<?= h($src) ?>"
                            alt="<?= h((string) ($banner['alt'] ?? 'Promoción LEODRI')) ?>"
                            width="<?= $ancho ?>"
                            height="<?= $alto ?>"
                            decoding="async"
                            sizes="100vw"
                            <?= $indice === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?>
                        >
                    </a>
                </article>
            <?php endforeach; ?>
        </div>

        <?php if (count($heroBanners) > 1): ?>
            <button
                type="button"
                class="tienda-hero__flecha tienda-hero__flecha--prev"
                aria-label="Banner anterior"
                data-hero-prev
            >
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <path d="M15 6l-6 6 6 6"/>
                </svg>
            </button>
            <button
                type="button"
                class="tienda-hero__flecha tienda-hero__flecha--next"
                aria-label="Banner siguiente"
                data-hero-next
            >
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <path d="M9 6l6 6-6 6"/>
                </svg>
            </button>
        <?php endif; ?>
    </div>

    <?php if (count($heroBanners) > 1): ?>
        <div class="tienda-hero__indicadores" role="tablist" aria-label="Seleccionar banner">
            <?php foreach ($heroBanners as $indice => $banner): ?>
                <button
                    type="button"
                    class="tienda-hero__punto<?= $indice === 0 ? ' is-active' : '' ?>"
                    role="tab"
                    aria-label="Banner <?= $indice + 1 ?>"
                    aria-selected="<?= $indice === 0 ? 'true' : 'false' ?>"
                    data-hero-dot="<?= $indice ?>"
                ></button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
