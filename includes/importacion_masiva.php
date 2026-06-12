<?php
declare(strict_types=1);

require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/repositorio_productos.php';

/**
 * Carga masiva simplificada — una fila = un producto.
 * Columnas: Marca | Nombre | Precio | Tallas | Stock | Color | Notas
 */

function importacion_columnas_etiquetas(): array
{
    return ['Marca', 'Nombre', 'Precio', 'Tallas', 'Stock', 'Color', 'Notas'];
}

function importacion_codigo_inventario(string $marca, string $nombre): string
{
    $m = preg_replace('/[^a-z0-9]/ui', '', $marca);
    $n = preg_replace('/[^a-z0-9]/ui', '', $nombre);
    $base = strtoupper(substr($m, 0, 3) . '-' . substr($n, 0, 8));

    return $base !== '-' ? $base : 'REF-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
}

function importacion_parsear_precio(string $raw): ?float
{
    $raw = trim(str_replace(['S/', 'S/ ', ' '], '', $raw));
    $raw = str_replace(',', '.', $raw);
    if ($raw === '' || !is_numeric($raw)) {
        return null;
    }

    $precio = (float) $raw;

    return $precio > 0 ? $precio : null;
}

function importacion_parsear_tallas(string $raw): array
{
    $raw = trim($raw);
    if ($raw === '') {
        return [];
    }

    if (preg_match('/^(\d+)\s*-\s*(\d+)$/', $raw, $m) === 1) {
        $inicio = (int) $m[1];
        $fin = (int) $m[2];
        if ($fin >= $inicio && ($fin - $inicio) <= 20) {
            $tallas = [];
            for ($n = $inicio; $n <= $fin; $n++) {
                $tallas[] = (string) $n;
            }

            return $tallas;
        }
    }

    $partes = preg_split('/[,;|\/]+/', $raw) ?: [];
    $tallas = [];
    foreach ($partes as $parte) {
        $numero = trim($parte);
        if ($numero !== '' && preg_match('/^\d{1,2}$/', $numero) === 1) {
            $tallas[] = $numero;
        }
    }

    return array_values(array_unique($tallas));
}

/**
 * @return array<string, int> talla => cantidad
 */
function importacion_parsear_stock(string $raw, array $tallas): array
{
    $raw = trim($raw);
    $mapa = [];

    if ($raw === '') {
        foreach ($tallas as $talla) {
            $mapa[$talla] = 0;
        }

        return $mapa;
    }

    if (str_contains($raw, ':')) {
        foreach (preg_split('/[,;|]+/', $raw) ?: [] as $par) {
            if (preg_match('/^(\d{1,2})\s*:\s*(\d+)$/', trim($par), $m) === 1) {
                $mapa[$m[1]] = max(0, (int) $m[2]);
            }
        }
        foreach ($tallas as $talla) {
            if (!isset($mapa[$talla])) {
                $mapa[$talla] = 0;
            }
        }

        return $mapa;
    }

    $cantidad = max(0, (int) preg_replace('/\D/', '', $raw));
    foreach ($tallas as $talla) {
        $mapa[$talla] = $cantidad;
    }

    return $mapa;
}

function importacion_es_fila_encabezado(array $cols): bool
{
    $unido = strtolower(implode(' ', $cols));

    return str_contains($unido, 'marca') && str_contains($unido, 'nombre');
}

function importacion_normalizar_fila(array $cols): array
{
    while (count($cols) < 7) {
        $cols[] = '';
    }

    return [
        'marca' => trim((string) ($cols[0] ?? '')),
        'nombre' => trim((string) ($cols[1] ?? '')),
        'precio_raw' => trim((string) ($cols[2] ?? '')),
        'tallas_raw' => trim((string) ($cols[3] ?? '')),
        'stock_raw' => trim((string) ($cols[4] ?? '')),
        'color' => trim((string) ($cols[5] ?? '')),
        'notas' => trim((string) ($cols[6] ?? '')),
    ];
}

function importacion_parsear_texto(string $texto): array
{
    $lineas = preg_split('/\r\n|\n|\r/', trim($texto)) ?: [];
    $filas = [];

    foreach ($lineas as $linea) {
        $linea = trim($linea);
        if ($linea === '') {
            continue;
        }

        if (str_contains($linea, "\t")) {
            $cols = explode("\t", $linea);
        } elseif (substr_count($linea, ';') > substr_count($linea, ',')) {
            $cols = str_getcsv($linea, ';');
        } else {
            $cols = str_getcsv($linea);
        }

        if (importacion_es_fila_encabezado($cols)) {
            continue;
        }

        $fila = importacion_normalizar_fila($cols);
        if ($fila['marca'] === '' && $fila['nombre'] === '') {
            continue;
        }

        $filas[] = $fila;
    }

    return $filas;
}

function importacion_validar_fila(array $fila, int $numeroFila): array
{
    $errores = [];

    if ($fila['marca'] === '') {
        $errores[] = 'Fila ' . $numeroFila . ': falta la marca.';
    }
    if ($fila['nombre'] === '') {
        $errores[] = 'Fila ' . $numeroFila . ': falta el nombre.';
    }

    $precio = importacion_parsear_precio($fila['precio_raw']);
    if ($precio === null) {
        $errores[] = 'Fila ' . $numeroFila . ': precio inválido.';
    }

    $tallas = importacion_parsear_tallas($fila['tallas_raw']);
    if ($tallas === []) {
        $errores[] = 'Fila ' . $numeroFila . ': indica al menos una talla (ej. 21,22,23 o 21-26).';
    }

    return $errores;
}

function importacion_fila_a_producto(array $fila): array
{
    $precio = importacion_parsear_precio($fila['precio_raw']);
    $tallasNumeros = importacion_parsear_tallas($fila['tallas_raw']);
    $stockMapa = importacion_parsear_stock($fila['stock_raw'], $tallasNumeros);
    $colorEtiqueta = $fila['color'] !== '' ? $fila['color'] : 'Único';
    $codigoInv = importacion_codigo_inventario($fila['marca'], $fila['nombre']);
    $sku = admin_generar_sku($codigoInv, 0, $tallasNumeros[0] ?? null);

    $descripcion = $fila['notas'] !== ''
        ? $fila['notas']
        : trim($fila['marca'] . ' ' . $fila['nombre']);

    $variantes = [];
    $tallas = [];
    foreach ($tallasNumeros as $i => $numero) {
        $cantidad = $stockMapa[$numero] ?? 0;
        $variantes[$numero] = $cantidad > 0;
        $tallas[] = [
            'numero' => $numero,
            'disponible' => $cantidad > 0,
            'orden' => $i,
        ];
    }

    $imagenesVacias = [];
    foreach (imagenes_vistas() as $vista) {
        $imagenesVacias[$vista] = '';
    }

    return [
        'datos' => [
            'marca' => $fila['marca'],
            'nombre' => $fila['nombre'],
            'descripcion' => $descripcion,
            'bullets' => [],
            'tags' => [],
            'precio' => $precio,
            'precio_anterior' => producto_precio_anterior_sugerido($precio),
            'aplicar_descuento' => true,
            'serie' => 'escolar',
            'color_default' => 'C1',
            'activo' => false,
            'orden' => 0,
            'colores' => [
                [
                    'codigo' => 'C1',
                    'etiqueta' => $colorEtiqueta,
                    'imagen' => '',
                    'imagenes' => $imagenesVacias,
                    'alt' => trim($fila['marca'] . ' ' . $fila['nombre'] . ', ' . $colorEtiqueta),
                    'sku_base' => $sku['sku_base'],
                    'sku_sin_talla' => $sku['sku_sin_talla'],
                    'variantes' => $variantes,
                ],
            ],
            'tallas' => array_map(static function (array $t): array {
                return [
                    'numero' => $t['numero'],
                    'disponible' => $t['disponible'],
                ];
            }, $tallas),
            'beneficios' => [],
        ],
        'stock_mapa' => $stockMapa,
    ];
}

function inventario_aplicar_stock_inicial(int $productoId, string $colorCodigo, array $stockPorTalla): void
{
    db_migrar_variantes();
    $pdo = db();

    $stmtVariante = $pdo->prepare(
        'UPDATE producto_variantes SET disponible = :disponible
         WHERE producto_id = :id AND color_codigo = :color AND talla_numero = :talla'
    );

    foreach ($stockPorTalla as $talla => $cantidad) {
        $cantidad = max(0, (int) $cantidad);
        $stmtVariante->execute([
            'disponible' => $cantidad > 0 ? 1 : 0,
            'id' => $productoId,
            'color' => $colorCodigo,
            'talla' => (string) $talla,
        ]);
    }

    if (!db_tiene_inventario_variantes()) {
        return;
    }

    inventario_sincronizar_producto($productoId);

    $stmtStock = $pdo->prepare(
        'UPDATE inventario_variantes iv
         INNER JOIN producto_colores pc ON pc.id = iv.producto_color_id
         INNER JOIN producto_tallas pt ON pt.id = iv.producto_talla_id
         SET iv.stock = :stock, iv.stock_reservado = 0
         WHERE iv.producto_id = :producto_id
           AND pc.codigo = :color
           AND pt.numero = :talla'
    );

    foreach ($stockPorTalla as $talla => $cantidad) {
        $stmtStock->execute([
            'stock' => max(0, (int) $cantidad),
            'producto_id' => $productoId,
            'color' => $colorCodigo,
            'talla' => (string) $talla,
        ]);
    }
}

function importacion_ejecutar(array $filas): array
{
    db_migrar_imagenes();
    db_migrar_variantes();
    inventario_sincronizar_productos_pendientes();

    $errores = [];
    $creados = 0;
    $ids = [];

    foreach ($filas as $i => $fila) {
        $numeroFila = $i + 1;
        $validacion = importacion_validar_fila($fila, $numeroFila);
        if ($validacion !== []) {
            $errores = array_merge($errores, $validacion);
            continue;
        }

        try {
            $preparado = importacion_fila_a_producto($fila);
            $productoId = producto_guardar(null, $preparado['datos']);
            inventario_aplicar_stock_inicial($productoId, 'C1', $preparado['stock_mapa']);
            $creados++;
            $ids[] = $productoId;
        } catch (Throwable $e) {
            $errores[] = 'Fila ' . $numeroFila . ': ' . $e->getMessage();
        }
    }

    if ($creados > 0) {
        productos_aplicar_reglas_catalogo();
    }

    return [
        'creados' => $creados,
        'ids' => $ids,
        'errores' => $errores,
    ];
}

function producto_subir_foto_principal(int $productoId, array $archivo): void
{
    db_migrar_imagenes();

    $pdo = db();
    $stmt = $pdo->prepare(
        'SELECT id, imagenes FROM producto_colores WHERE producto_id = :id ORDER BY orden ASC, id ASC LIMIT 1'
    );
    $stmt->execute(['id' => $productoId]);
    $color = $stmt->fetch();
    if (!$color) {
        throw new RuntimeException('El producto no tiene colores configurados.');
    }

    $ruta = imagenes_subir_archivo($archivo, $productoId, 0, 'derecha');
    if ($ruta === null) {
        throw new RuntimeException('No se recibió ninguna imagen.');
    }

    $imagenes = imagenes_normalizar_color(['imagenes' => $color['imagenes'] ?? []])['imagenes'];
    $imagenes['derecha'] = $ruta;
    if ($imagenes['frente'] === '') {
        $imagenes['frente'] = $ruta;
    }

    $imagen = imagen_thumbnail_desde_vistas($imagenes, $ruta);
    $json = json_encode($imagenes, JSON_UNESCAPED_UNICODE);

    $upd = $pdo->prepare(
        'UPDATE producto_colores SET imagen = :imagen, imagenes = :imagenes WHERE id = :id'
    );
    $upd->execute([
        'imagen' => $imagen,
        'imagenes' => $json,
        'id' => (int) $color['id'],
    ]);

    productos_aplicar_reglas_catalogo();
}
