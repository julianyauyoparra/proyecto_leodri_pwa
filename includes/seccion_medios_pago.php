<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

/** @var string $tiendaBase Prefijo de ruta (ej. '' desde raíz PWA) */
$tiendaBase = $tiendaBase ?? '';
?>
<section
    class="tienda-medios-pago"
    id="medios-pago"
    aria-labelledby="tienda-medios-pago-titulo"
>
    <div class="tienda-medios-pago__inner">
        <h2 id="tienda-medios-pago-titulo" class="tienda-medios-pago__titulo">Medios de pago</h2>
        <ul class="tienda-medios-pago__lista">
            <li class="tienda-medios-pago__item">
                <img
                    src="<?= h($tiendaBase) ?>assets/medios-pago/logo-yape.png"
                    alt="Yape"
                    class="tienda-medios-pago__logo tienda-medios-pago__logo--yape"
                    width="120"
                    height="48"
                    loading="lazy"
                    decoding="async"
                >
            </li>
            <li class="tienda-medios-pago__item">
                <img
                    src="<?= h($tiendaBase) ?>assets/medios-pago/logo-plin.png"
                    alt="Plin"
                    class="tienda-medios-pago__logo tienda-medios-pago__logo--plin"
                    width="64"
                    height="64"
                    loading="lazy"
                    decoding="async"
                >
            </li>
        </ul>
    </div>
</section>
