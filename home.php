<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#d80000">
    <meta name="robots" content="noindex, nofollow">
    <title>LEODRI — Vista previa tienda</title>
    <link rel="icon" href="assets/icons/icon-192.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/ficha.css">
</head>
<body class="pagina-tienda pagina-home">

    <?php
    require_once __DIR__ . '/includes/categorias_tienda.php';
    require_once __DIR__ . '/includes/catalogo.php';
    require_once __DIR__ . '/includes/repositorio_productos.php';

    $categoriaActiva = categoria_desde_request($_GET['categoria'] ?? null);
    $categoriaNavActiva = $categoriaActiva;

    try {
        $productosHome = productos_listar_por_categoria($categoriaActiva, HOME_PRODUCTOS_MAX);
    } catch (Throwable $e) {
        $productosHome = [];
    }

    $mostrarHeroBanner = true;
    require __DIR__ . '/includes/cabecera_tienda.php';
    ?>

    <main class="tienda-principal" id="contenido-principal" aria-label="Contenido principal">
        <?php require __DIR__ . '/includes/seccion_categoria_home.php'; ?>
    </main>

    <?php require __DIR__ . '/includes/ficha_modales.php'; ?>

    <?php
    require_once __DIR__ . '/includes/config.php';
    leodri_emitir_scripts_config();
    ?>
    <script src="js/hero.js"></script>
    <script src="js/home-categoria.js"></script>
    <script src="js/pwa.js"></script>
    <script src="js/ficha.js" defer></script>
</body>
</html>
