<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

/** @var string $tiendaBase Prefijo de ruta (ej. '' desde raíz PWA) */
$tiendaBase = $tiendaBase ?? '';

function footer_icono(string $nombre): string
{
    $iconos = [
        'ubicacion' => '<svg class="tienda-footer__icono-svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M12 21s7-4.5 7-11a7 7 0 10-14 0c0 6.5 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>',
        'correo' => '<svg class="tienda-footer__icono-svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg>',
        'whatsapp' => '<svg class="tienda-footer__icono-svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>',
        'horario' => '<svg class="tienda-footer__icono-svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18"/><path d="M8 15h2v2H8z"/></svg>',
        'faq' => '<svg class="tienda-footer__icono-svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M7.5 8.5a4.5 4.5 0 019 2c0 2.5-2 3-3.5 3.5-.5.2-.5.8-.5 1v.5"/><circle cx="12" cy="18" r=".75" fill="currentColor" stroke="none"/><path d="M8 9.5c0-2.2 1.8-4 4-4s4 1.8 4 4"/></svg>',
        'consulta' => '<svg class="tienda-footer__icono-svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/><path d="M11 8v.01M10 11h1a1.5 1.5 0 011.5 1.5c0 1-1 1.2-1.5 1.5V15"/></svg>',
        'comprar' => '<svg class="tienda-footer__icono-svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M9 11V6a3 3 0 116 0v5"/><rect x="7" y="11" width="10" height="9" rx="2"/><path d="M12 15v2"/></svg>',
        'pedidos' => '<svg class="tienda-footer__icono-svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M3 7h11v9H3z"/><path d="M14 10h4l3 3v3h-7V10z"/><circle cx="7.5" cy="18" r="1.5"/><circle cx="17.5" cy="18" r="1.5"/></svg>',
        'terminos' => '<svg class="tienda-footer__icono-svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M8 4h9a2 2 0 012 2v14l-4-2-4 2-4-2-4 2V6a2 2 0 012-2z"/><path d="M8 8h8M8 12h8M8 16h5"/></svg>',
        'privacidad' => '<svg class="tienda-footer__icono-svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 118 0v3"/></svg>',
    ];

    return $iconos[$nombre] ?? '';
}

$anioActual = (int) date('Y');
?>
<footer class="tienda-footer" id="tienda-footer">
    <div class="tienda-footer__inner">
        <a href="<?= h($tiendaBase === '' ? './' : $tiendaBase) ?>" class="tienda-footer__marca" aria-label="LEODRI — Inicio">
            <img
                src="<?= h($tiendaBase) ?>assets/logo-leodri-blanco.webp"
                alt="LEODRI — Calzado que marca tu camino"
                class="tienda-footer__logo"
                width="1024"
                height="256"
                loading="lazy"
                decoding="async"
            >
        </a>

        <hr class="tienda-footer__separador" aria-hidden="true">

        <div class="tienda-footer__columnas">
            <section class="tienda-footer__col" aria-labelledby="footer-contacto-titulo">
                <h2 id="footer-contacto-titulo" class="tienda-footer__titulo">Contáctenos</h2>
                <ul class="tienda-footer__lista">
                    <li class="tienda-footer__item">
                        <span class="tienda-footer__enlace tienda-footer__enlace--texto">
                            <span class="tienda-footer__icono"><?= footer_icono('ubicacion') ?></span>
                            <span>Jr Sancos 209 - Puquio</span>
                        </span>
                    </li>
                    <li class="tienda-footer__item">
                        <a class="tienda-footer__enlace" href="mailto:informes@leodri.pe">
                            <span class="tienda-footer__icono"><?= footer_icono('correo') ?></span>
                            <span>informes@leodri.pe</span>
                        </a>
                    </li>
                    <li class="tienda-footer__item">
                        <a class="tienda-footer__enlace" href="https://wa.me/51935486809" target="_blank" rel="noopener noreferrer">
                            <span class="tienda-footer__icono"><?= footer_icono('whatsapp') ?></span>
                            <span>935 486 809</span>
                        </a>
                    </li>
                    <li class="tienda-footer__item">
                        <span class="tienda-footer__enlace tienda-footer__enlace--texto">
                            <span class="tienda-footer__icono"><?= footer_icono('horario') ?></span>
                            <span>Lunes a viernes de 8:00 a 18:30</span>
                        </span>
                    </li>
                </ul>
            </section>

            <section class="tienda-footer__col" aria-labelledby="footer-ayuda-titulo">
                <h2 id="footer-ayuda-titulo" class="tienda-footer__titulo">Centro de ayuda</h2>
                <ul class="tienda-footer__lista">
                    <li class="tienda-footer__item">
                        <a class="tienda-footer__enlace" href="<?= h($tiendaBase) ?>ayuda/preguntas-frecuentes.php">
                            <span class="tienda-footer__icono"><?= footer_icono('faq') ?></span>
                            <span>Preguntas Frecuentes</span>
                        </a>
                    </li>
                    <li class="tienda-footer__item">
                        <a class="tienda-footer__enlace" href="mailto:informes@leodri.pe?subject=Consultas%20y%20sugerencias%20LEODRI">
                            <span class="tienda-footer__icono"><?= footer_icono('consulta') ?></span>
                            <span>Consultas y sugerencias</span>
                        </a>
                    </li>
                    <li class="tienda-footer__item">
                        <a class="tienda-footer__enlace" href="<?= h($tiendaBase) ?>ayuda/como-comprar.php">
                            <span class="tienda-footer__icono"><?= footer_icono('comprar') ?></span>
                            <span>Cómo comprar</span>
                        </a>
                    </li>
                    <li class="tienda-footer__item">
                        <a class="tienda-footer__enlace" href="<?= h($tiendaBase) ?>ayuda/mis-pedidos.php">
                            <span class="tienda-footer__icono"><?= footer_icono('pedidos') ?></span>
                            <span>Mis pedidos</span>
                        </a>
                    </li>
                </ul>
            </section>

            <section class="tienda-footer__col" aria-labelledby="footer-legal-titulo">
                <h2 id="footer-legal-titulo" class="tienda-footer__titulo">Información legal</h2>
                <ul class="tienda-footer__lista">
                    <li class="tienda-footer__item">
                        <a class="tienda-footer__enlace" href="<?= h($tiendaBase) ?>legal/terminos.php">
                            <span class="tienda-footer__icono"><?= footer_icono('terminos') ?></span>
                            <span>Términos y Condiciones</span>
                        </a>
                    </li>
                    <li class="tienda-footer__item">
                        <a class="tienda-footer__enlace" href="<?= h($tiendaBase) ?>legal/privacidad.php">
                            <span class="tienda-footer__icono"><?= footer_icono('privacidad') ?></span>
                            <span>Políticas de Privacidad</span>
                        </a>
                    </li>
                </ul>
            </section>
        </div>

        <hr class="tienda-footer__separador" aria-hidden="true">

        <div class="tienda-footer__redes" aria-label="Redes sociales">
            <a class="tienda-footer__red" href="https://www.facebook.com/leodri.pe" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M14 8.5h2.5L16 12h-2v8h-3v-8H9v-3.5h2V7.2C11 4.9 12.4 3 15.2 3H17v3h-1.4c-1 0-1.1.5-1.1 1.2V8.5z"/></svg>
            </a>
            <a class="tienda-footer__red" href="https://www.tiktok.com/@leodri.pe" target="_blank" rel="noopener noreferrer" aria-label="TikTok">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M16.5 3c.4 2.2 1.8 3.9 3.8 4.5V11c-1.4 0-2.7-.4-3.8-1.1v5.8c0 3.4-2.7 5.8-5.8 5.8S4.9 19.1 4.9 15.7 7.6 10 10.7 10c.3 0 .7 0 1 .1v3.4c-.3-.1-.6-.2-1-.2-1.5 0-2.7 1.2-2.7 2.7s1.2 2.7 2.7 2.7 2.7-1.2 2.7-2.7V3h2.8z"/></svg>
            </a>
            <a class="tienda-footer__red" href="https://www.instagram.com/leodri.pe" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><rect x="4" y="4" width="16" height="16" rx="4"/><circle cx="12" cy="12" r="3.5"/><circle cx="17.2" cy="6.8" r=".8" fill="currentColor" stroke="none"/></svg>
            </a>
        </div>

        <p class="tienda-footer__copy"><?= h((string) $anioActual) ?></p>
    </div>
</footer>
