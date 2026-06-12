<?php
declare(strict_types=1);

function series_opciones(): array
{
    return [
        'damas' => 'Damas',
        'caballero' => 'Caballero',
        'baby' => 'Baby / No caminante',
        'inicial' => 'Inicial / Párvulo',
        'escolar' => 'Escolar / Juvenil',
    ];
}

function series_es_valida(string $serie): bool
{
    return isset(series_opciones()[$serie]);
}

function series_normalizar(string $serie): string
{
    $serie = strtolower(trim($serie));

    return series_es_valida($serie) ? $serie : 'escolar';
}

function producto_precio_anterior_sugerido(float $precio): float
{
    if ($precio <= 0) {
        return 0.0;
    }

    return round($precio * 1.27, 2);
}

function producto_descuento_porcentaje(float $precio, float $precioAnterior): int
{
    if ($precioAnterior <= 0 || $precio <= 0 || $precioAnterior <= $precio) {
        return 0;
    }

    return (int) round((1 - ($precio / $precioAnterior)) * 100);
}

function producto_leer_aplicar_descuento(array $fila, bool $default = true): bool
{
    if (!array_key_exists('aplicar_descuento', $fila)) {
        return $default;
    }

    return (int) $fila['aplicar_descuento'] === 1;
}

function series_guia_titulo(string $serie): string
{
    $titulos = [
        'damas' => 'Guía de Tallas — Damas',
        'caballero' => 'Guía de Tallas — Caballero',
        'baby' => 'Guía de Tallas — Baby',
        'inicial' => 'Guía de Tallas — Inicial / Párvulo',
        'escolar' => 'Guía de Tallas — Escolar / Juvenil',
    ];

    return $titulos[series_normalizar($serie)] ?? $titulos['escolar'];
}

function series_guia_intro(string $serie): string
{
    $intros = [
        'damas' => 'Serie comercial damas (EU 35–40). Mide el pie con regla; si dudas entre dos tallas, elige la mayor.',
        'caballero' => 'Serie comercial caballero (EU 38–43). Las tallas 40 y 41 suelen agotarse primero.',
        'baby' => 'Serie baby para no caminantes (EU 16–21). El pie crece rápido; revisa cada 2–3 meses.',
        'inicial' => 'Serie inicial / párvulo (EU 22–27). Concentración de venta en tallas 24 y 25.',
        'escolar' => 'Serie escolar / juvenil (EU 28–35). Muy usada en campañas de inicio de clases.',
    ];

    return $intros[series_normalizar($serie)] ?? $intros['escolar'];
}

/** @return list<array{talla: string, cm: string, ref: string}> */
function series_guia_filas(string $serie): array
{
    $tablas = [
        'damas' => [
            ['talla' => '35', 'cm' => '22.0 cm', 'ref' => 'Poco frecuente en serie'],
            ['talla' => '36', 'cm' => '22.8 cm', 'ref' => 'Alta rotación'],
            ['talla' => '37', 'cm' => '23.5 cm', 'ref' => 'Mayor demanda'],
            ['talla' => '38', 'cm' => '24.2 cm', 'ref' => 'Alta rotación'],
            ['talla' => '39', 'cm' => '25.0 cm', 'ref' => 'Media demanda'],
            ['talla' => '40', 'cm' => '25.7 cm', 'ref' => 'Poco frecuente en serie'],
        ],
        'caballero' => [
            ['talla' => '38', 'cm' => '24.2 cm', 'ref' => 'Entrada de serie'],
            ['talla' => '39', 'cm' => '25.0 cm', 'ref' => 'Media demanda'],
            ['talla' => '40', 'cm' => '25.7 cm', 'ref' => 'Mayor demanda'],
            ['talla' => '41', 'cm' => '26.5 cm', 'ref' => 'Mayor demanda'],
            ['talla' => '42', 'cm' => '27.2 cm', 'ref' => 'Media demanda'],
            ['talla' => '43', 'cm' => '28.0 cm', 'ref' => 'Poco frecuente en serie'],
        ],
        'baby' => [
            ['talla' => '16', 'cm' => '9.5 cm', 'ref' => '0–6 meses aprox.'],
            ['talla' => '17', 'cm' => '10.2 cm', 'ref' => '6–9 meses aprox.'],
            ['talla' => '18', 'cm' => '10.9 cm', 'ref' => '9–12 meses aprox.'],
            ['talla' => '19', 'cm' => '11.5 cm', 'ref' => '12–15 meses aprox.'],
            ['talla' => '20', 'cm' => '12.2 cm', 'ref' => '15–18 meses aprox.'],
            ['talla' => '21', 'cm' => '13.0 cm', 'ref' => '18–24 meses aprox.'],
        ],
        'inicial' => [
            ['talla' => '22', 'cm' => '13.5 cm', 'ref' => '2 años aprox.'],
            ['talla' => '23', 'cm' => '14.2 cm', 'ref' => '2–3 años aprox.'],
            ['talla' => '24', 'cm' => '15.0 cm', 'ref' => 'Alta rotación'],
            ['talla' => '25', 'cm' => '15.5 cm', 'ref' => 'Alta rotación'],
            ['talla' => '26', 'cm' => '16.2 cm', 'ref' => '3–4 años aprox.'],
            ['talla' => '27', 'cm' => '16.8 cm', 'ref' => '4 años aprox.'],
        ],
        'escolar' => [
            ['talla' => '28', 'cm' => '17.5 cm', 'ref' => '5 años aprox.'],
            ['talla' => '29', 'cm' => '18.2 cm', 'ref' => '6 años aprox.'],
            ['talla' => '30', 'cm' => '18.8 cm', 'ref' => '7 años aprox.'],
            ['talla' => '31', 'cm' => '19.5 cm', 'ref' => '8 años aprox.'],
            ['talla' => '32', 'cm' => '20.2 cm', 'ref' => '9 años aprox.'],
            ['talla' => '33', 'cm' => '20.8 cm', 'ref' => '10 años aprox.'],
            ['talla' => '34', 'cm' => '21.5 cm', 'ref' => '11 años aprox.'],
            ['talla' => '35', 'cm' => '22.0 cm', 'ref' => '12 años aprox.'],
        ],
    ];

    return $tablas[series_normalizar($serie)] ?? $tablas['escolar'];
}

function series_guia_pasos_html(): string
{
    return <<<'HTML'
<ol class="ficha-guia__pasos">
    <li>Coloca el pie sobre una hoja de papel en el suelo.</li>
    <li>Marca desde el talón hasta la punta del dedo más largo.</li>
    <li>Mide con regla y suma 0.5 cm para mayor comodidad.</li>
</ol>
HTML;
}

function series_render_guia_html(string $serie): string
{
    $serie = series_normalizar($serie);
    $filas = series_guia_filas($serie);
    $intro = h(series_guia_intro($serie));
    $pasos = series_guia_pasos_html();

    $tbody = '';
    foreach ($filas as $fila) {
        $tbody .= '<tr><td>' . h($fila['talla']) . '</td><td>' . h($fila['cm']) . '</td><td>' . h($fila['ref']) . '</td></tr>';
    }

    return <<<HTML
<p class="ficha-guia__intro">{$intro}</p>
{$pasos}
<div class="ficha-guia__tabla-wrap">
    <table class="ficha-guia__tabla">
        <thead>
            <tr>
                <th scope="col">Talla (EU)</th>
                <th scope="col">Medida del pie</th>
                <th scope="col">Referencia</th>
            </tr>
        </thead>
        <tbody>{$tbody}</tbody>
    </table>
</div>
<p class="ficha-guia__nota">¿Dudas entre dos tallas? Te recomendamos elegir la mayor.</p>
HTML;
}
