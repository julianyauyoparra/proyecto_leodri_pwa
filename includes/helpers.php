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

function icono_beneficio(string $tipo): string
{
    $iconos = [
        'zap' => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L4 14h7l-1 8 10-14h-7l0-6z"/></svg>',
        'check' => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>',
        'wave' => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M4 14c2-4 4-4 6-2s4 2 6-2 4-2 4-2"/><path d="M4 10c2-4 4-4 6-2s4 2 6-2 4-2 4-2"/></svg>',
        'shoe' => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M6 18h12"/><path d="M8 18V8a4 4 0 018 0v10"/><path d="M5 18c0-1 1-2 3-2h8c2 0 3 1 3 2"/></svg>',
        'shield' => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 4v5c0 4-3 7-7 9-4-2-7-5-7-9V7l7-4z"/></svg>',
    ];

    return $iconos[$tipo] ?? $iconos['check'];
}
