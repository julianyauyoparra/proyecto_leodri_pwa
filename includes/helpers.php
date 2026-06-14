<?php
declare(strict_types=1);

function h(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

function formatear_precio(float $precio): string
{
    return 'S/ ' . number_format($precio, 2, '.', '');
}
