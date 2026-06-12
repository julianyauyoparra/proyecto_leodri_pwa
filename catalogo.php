<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/catalogo.php';
require_once __DIR__ . '/includes/series_tallas.php';

$productos = cargar_catalogo();
$ogImagen = 'https://leodri.pe/assets/demo/hero-rjn.png';

if ($productos !== []) {
    $primerColor = color_por_defecto($productos[0]);
    if ($primerColor && !empty($primerColor['imagen'])) {
        $ogImagen = 'https://leodri.pe/' . ltrim($primerColor['imagen'], '/');
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#d80000">
    <meta name="description" content="LEODRI — Catálogo de calzado. Selección curada y compra por WhatsApp.">
    <link rel="canonical" href="https://leodri.pe/catalogo.php">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="es_PE">
    <meta property="og:site_name" content="LEODRI">
    <meta property="og:url" content="https://leodri.pe/catalogo.php">
    <meta property="og:title" content="LEODRI — Catálogo">
    <meta property="og:description" content="Elige talla y color, y pide por WhatsApp en segundos.">
    <meta property="og:image" content="<?= h($ogImagen) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <title>LEODRI — Catálogo</title>
    <link rel="manifest" href="manifest.webmanifest">
    <link rel="icon" href="assets/icons/icon-192.png" type="image/png">
    <link rel="apple-touch-icon" href="assets/icons/apple-touch-icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/ficha.css">
</head>
<body class="pagina-ficha pagina-tienda">

    <?php require __DIR__ . '/includes/cabecera_tienda.php'; ?>

    <main class="catalogo" aria-label="Catálogo de productos">
        <?php if ($productos === []): ?>
            <p class="catalogo-vacio">No hay productos disponibles en este momento.</p>
        <?php else: ?>
            <?php foreach ($productos as $producto): ?>
                <?php require __DIR__ . '/includes/ficha.php'; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <?php require __DIR__ . '/includes/ficha_modales.php'; ?>

    <?php
    require_once __DIR__ . '/includes/config.php';
    leodri_emitir_scripts_config();
    ?>
    <script src="js/pwa.js"></script>
    <script src="js/ficha.js" defer></script>
</body>
</html>
