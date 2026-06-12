<?php
declare(strict_types=1);

/** Máximo de productos visibles en la sección home por categoría */
const HOME_PRODUCTOS_MAX = 8;

/** Categoría por defecto en la home */
const CATEGORIA_HOME_DEFAULT = 'zapatillas';

/**
 * Categorías disponibles en el menú principal de la tienda.
 * Agregar aquí nuevas entradas (ej. 'zapatos' => 'Zapatos') cuando corresponda.
 *
 * @return array<string, string> slug => etiqueta visible
 */
function categorias_tienda_nav(): array
{
    return [
        'zapatillas' => 'Zapatillas',
    ];
}

function categoria_normalizar(string $slug): string
{
    $slug = strtolower(trim($slug));
    $slug = preg_replace('/[^a-z0-9_-]+/', '', $slug) ?? '';

    return $slug;
}

function categoria_es_valida(string $slug): bool
{
    $slug = categoria_normalizar($slug);

    return $slug !== '' && array_key_exists($slug, categorias_tienda_nav());
}

function categoria_desde_request(?string $slug): string
{
    $normalizada = categoria_normalizar((string) $slug);
    if (categoria_es_valida($normalizada)) {
        return $normalizada;
    }

    return CATEGORIA_HOME_DEFAULT;
}

function categoria_etiqueta(string $slug): string
{
    $slug = categoria_normalizar($slug);

    return categorias_tienda_nav()[$slug] ?? ucfirst($slug);
}
