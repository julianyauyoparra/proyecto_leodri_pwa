<?php
declare(strict_types=1);

/**
 * Copia como database.php en cPanel (producción). No commitear credenciales reales.
 *
 * Local: usa database.local.php (MySQL Docker) — no necesitas este archivo en tu PC.
 */
return [
    'host' => 'acela.proxy.rlwy.net',
    'port' => 20476,
    'dbname' => 'railway',
    'user' => 'root',
    'pass' => 'TU_CLAVE_MYSQL_RAILWAY',
    'charset' => 'utf8mb4',
];
