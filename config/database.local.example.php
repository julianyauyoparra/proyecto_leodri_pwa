<?php
declare(strict_types=1);

/**
 * Copia como database.local.php (solo tu PC; no subir a cPanel).
 * Prioridad sobre database.php cuando existe.
 *
 * Requiere: docker compose up -d en ProyectoLeodri/docker
 */
return [
    'host' => '127.0.0.1',
    'port' => 3307,
    'dbname' => 'leodri',
    'user' => 'leodri',
    'pass' => 'leodri_dev',
    'charset' => 'utf8mb4',
];
