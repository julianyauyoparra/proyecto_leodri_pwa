<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/admin_auth.php';
require_once dirname(__DIR__) . '/includes/repositorio_productos.php';
require_once dirname(__DIR__) . '/includes/admin_layout.php';

admin_requerir_login();

$totalProductos = productos_contar();

admin_layout_inicio('Dashboard', true, 'admin-body--dashboard');
?>
<section class="admin-dashboard" aria-label="Panel de administración">
    <h1 class="admin-dashboard__titulo">Dashboard</h1>

    <div class="admin-dashboard__grid">
        <a href="producto.php" class="admin-dashboard__card">
            <span class="admin-dashboard__icono" aria-hidden="true">
                <svg width="72" height="72" viewBox="0 0 72 72" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="14" y="10" width="44" height="52" rx="4" stroke="currentColor" stroke-width="3"/>
                    <path d="M22 22h28M22 30h28M22 38h18" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                    <path d="M44 46l14 8-5 5-9-14" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M41 49l3 3" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                </svg>
            </span>
            <span class="admin-dashboard__texto">Registro de Productos Individuales</span>
        </a>

        <a href="carga_masiva.php" class="admin-dashboard__card">
            <span class="admin-dashboard__icono" aria-hidden="true">
                <svg width="72" height="72" viewBox="0 0 72 72" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="14" y="10" width="44" height="52" rx="4" stroke="currentColor" stroke-width="3"/>
                    <path d="M22 22h28M22 30h28M22 38h28M22 46h20" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                    <path d="M44 46l14 8-5 5-9-14" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M41 49l3 3" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                </svg>
            </span>
            <span class="admin-dashboard__texto">Registro de Productos Masivo</span>
        </a>

        <a href="productos.php" class="admin-dashboard__card">
            <span class="admin-dashboard__icono" aria-hidden="true">
                <svg width="72" height="72" viewBox="0 0 72 72" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="12" y="14" width="22" height="22" rx="3" stroke="currentColor" stroke-width="3"/>
                    <rect x="38" y="14" width="22" height="22" rx="3" stroke="currentColor" stroke-width="3"/>
                    <rect x="12" y="40" width="22" height="22" rx="3" stroke="currentColor" stroke-width="3"/>
                    <rect x="38" y="40" width="22" height="22" rx="3" stroke="currentColor" stroke-width="3"/>
                </svg>
            </span>
            <span class="admin-dashboard__texto">Ver Productos Cargados</span>
            <?php if ($totalProductos > 0): ?>
                <span class="admin-dashboard__badge"><?= (int) $totalProductos ?> en total</span>
            <?php endif; ?>
        </a>

        <a href="banners.php" class="admin-dashboard__card">
            <span class="admin-dashboard__icono" aria-hidden="true">
                <svg width="72" height="72" viewBox="0 0 72 72" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="8" y="18" width="56" height="36" rx="4" stroke="currentColor" stroke-width="3"/>
                    <path d="M8 28h56" stroke="currentColor" stroke-width="2"/>
                    <path d="M20 46h16" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                    <circle cx="54" cy="46" r="6" stroke="currentColor" stroke-width="2.5"/>
                    <path d="M52 46h4M54 44v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </span>
            <span class="admin-dashboard__texto">Agregar banner</span>
        </a>
    </div>
</section>
<?php
admin_layout_fin();
