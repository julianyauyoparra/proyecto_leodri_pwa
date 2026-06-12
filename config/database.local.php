<?php
declare(strict_types=1);

/**
 * Desarrollo local — MySQL Docker (docker/.env → puerto 3307).
 * Este archivo no se sube a cPanel (está en .gitignore).
 */
return [
    'host' => '127.0.0.1',
    'port' => 3307,
    'dbname' => 'leodri',
    'user' => 'leodri',
    'pass' => 'leodri_dev',
    'charset' => 'utf8mb4',
];
