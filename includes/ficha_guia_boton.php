<?php
declare(strict_types=1);

/** @var string $ubicacion 'movil'|'desktop' */
/** @var bool $tieneGuia */

if (empty($tieneGuia)) {
    return;
}
?>
<button
    type="button"
    class="ficha-guia-btn ficha-guia-btn--<?= h($ubicacion) ?>"
    data-guia
    aria-label="Ver guía de tallas"
>Guía de tallas</button>
