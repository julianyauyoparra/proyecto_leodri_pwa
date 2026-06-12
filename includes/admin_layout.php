<?php

declare(strict_types=1);



function admin_layout_inicio(string $titulo, bool $mostrarNav = true, string $claseBody = ''): void

{

    $clases = trim('admin-body ' . $claseBody);

    ?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#d80000">

    <meta name="robots" content="noindex, nofollow">

    <title><?= h($titulo) ?> — LEODRI Admin</title>

    <link rel="icon" href="../assets/icons/favicon-32.png" type="image/png" sizes="32x32">

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="css/admin.css">

</head>

<body class="<?= h($clases) ?>">

    <?php if ($mostrarNav && admin_esta_logueado()): ?>

    <header class="admin-header">

        <div class="admin-header__bar">

            <a href="index.php" class="admin-header__logo" aria-label="LEODRI — Inicio admin">

                <img

                    src="../assets/logo-leodri-oficial.png"

                    alt="LEODRI — Calzados que dirigen tu camino"

                    class="admin-header__logo-img"

                    width="1024"

                    height="321"

                >

            </a>

            <nav class="admin-header__user" aria-label="Usuario">

                <span class="admin-header__admin">

                    <svg class="admin-header__icono" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">

                        <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.75"/>

                        <path d="M5 20c0-4 3-6 7-6s7 2 7 6" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>

                    </svg>

                    Admin

                </span>

                <a href="logout.php" class="admin-header__salir">

                    <svg class="admin-header__icono" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">

                        <path d="M9 6V4a1 1 0 011-1h8a1 1 0 011 1v16a1 1 0 01-1 1h-8a1 1 0 01-1-1v-2" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>

                        <path d="M13 12H3m0 0l3-3M3 12l3 3" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>

                    </svg>

                    Salir

                </a>

            </nav>

        </div>

        <div class="admin-header__rule" aria-hidden="true"></div>

    </header>

    <?php endif; ?>

    <main class="admin-wrap">

    <?php

}



function admin_layout_fin(): void

{

    ?>

    </main>

    </body>

    </html>

    <?php

}


