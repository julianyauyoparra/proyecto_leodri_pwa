<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

/** @var string $tiendaBase Prefijo de ruta (ej. '' desde raíz PWA) */
$tiendaBase = $tiendaBase ?? '';
$buscarQuery = isset($buscarQuery) ? (string) $buscarQuery : (string) ($_GET['q'] ?? '');
$buscarAction = $tiendaBase . 'home.php';
/** @var bool $mostrarHeroBanner Mostrar carrusel bajo el menú (solo home) */
$mostrarHeroBanner = $mostrarHeroBanner ?? false;
?>
<div class="tienda-sitio-top">
    <div class="tienda-sitio-top__sticky">
        <header class="tienda-cabecera">
        <div class="tienda-cabecera__inner">
            <a href="<?= h($tiendaBase === '' ? './' : $tiendaBase) ?>" class="tienda-cabecera__marca" aria-label="LEODRI — Inicio">
                <img
                    src="<?= h($tiendaBase) ?>assets/logo-leodri-oficial.png"
                    alt="LEODRI — Calzado que marca tu camino"
                    class="tienda-cabecera__logo"
                    width="1024"
                    height="321"
                >
            </a>

            <form class="tienda-cabecera__buscar" action="<?= h($buscarAction) ?>" method="get" role="search">
                <label class="tienda-cabecera__buscar-label" for="tienda-buscar">Buscar productos</label>
                <span class="tienda-cabecera__buscar-icono" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <circle cx="11" cy="11" r="7"/>
                        <path d="M20 20l-4-4"/>
                    </svg>
                </span>
                <input
                    type="search"
                    id="tienda-buscar"
                    name="q"
                    class="tienda-cabecera__buscar-input"
                    placeholder="Buscar"
                    value="<?= h($buscarQuery) ?>"
                    autocomplete="off"
                    enterkeyhint="search"
                >
            </form>

            <p class="tienda-cabecera__entrega" aria-label="Te entregamos en tu domicilio">
                <img
                    src="<?= h($tiendaBase) ?>assets/icono-entrega-domicilio.png"
                    alt=""
                    class="tienda-cabecera__entrega-icono"
                    width="150"
                    height="150"
                >
                <span class="tienda-cabecera__entrega-texto">Te entregamos en tu domicilio</span>
            </p>
        </div>
    </header>

        <?php require __DIR__ . '/nav_tienda.php'; ?>
    </div>

    <?php if ($mostrarHeroBanner): ?>
        <?php require __DIR__ . '/hero_banner.php'; ?>
    <?php endif; ?>
</div>
