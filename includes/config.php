<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

/**
 * Resuelve config/{nombre}.local.php (desarrollo) o config/{nombre}.php (producción).
 */
function config_ruta(string $nombre): ?string
{
    $dir = dirname(__DIR__) . '/config';
    $local = $dir . '/' . $nombre . '.local.php';
    if (is_readable($local)) {
        return $local;
    }

    $prod = $dir . '/' . $nombre . '.php';
    if (is_readable($prod)) {
        return $prod;
    }

    return null;
}

function config_cargar(string $nombre): array
{
    static $cache = [];

    if (isset($cache[$nombre])) {
        return $cache[$nombre];
    }

    $ruta = config_ruta($nombre);
    if ($ruta === null) {
        throw new RuntimeException(
            'Falta config/' . $nombre . '.local.php (local) o config/' . $nombre . '.php (producción). '
            . 'Copia desde config/' . $nombre . '.example.php o ' . $nombre . '.local.example.php.'
        );
    }

    $datos = require $ruta;
    if (!is_array($datos)) {
        throw new RuntimeException('Config inválida: ' . $ruta);
    }

    $cache[$nombre] = $datos;

    return $datos;
}

function config_cargar_opcional(string $nombre, array $predeterminado = []): array
{
    $ruta = config_ruta($nombre);
    if ($ruta === null) {
        return $predeterminado;
    }

    $datos = require $ruta;

    return is_array($datos) ? $datos : $predeterminado;
}

function leodri_emitir_scripts_config(): void
{
    echo '<script src="js/leodri-config.js"></script>' . "\n";

    $local = dirname(__DIR__) . '/js/leodri-config.local.js';
    if (is_readable($local)) {
        echo '<script src="js/leodri-config.local.js"></script>' . "\n";
    }
}
