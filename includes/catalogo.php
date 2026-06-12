<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

function color_por_defecto(array $producto): ?array
{
    $colores = $producto['colores'] ?? [];
    if ($colores === []) {
        return null;
    }

    $codigo = $producto['color_default'] ?? '';
    foreach ($colores as $color) {
        if (($color['codigo'] ?? '') === $codigo) {
            return $color;
        }
    }

    return $colores[0];
}
