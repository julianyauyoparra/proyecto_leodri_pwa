<?php
declare(strict_types=1);

require_once __DIR__ . '/imagenes_producto.php';
require_once __DIR__ . '/repositorio_series.php';
require_once __DIR__ . '/categorias_tienda.php';

const CARGA_NOMENCLATURA_VISTAS = ['derecha', 'izquierda', 'posterior', 'arriba', 'abajo', 'frente'];

function carga_nomenclatura_ejemplo(): string
{
    return 'Zapatilla_Adidas_Geforce1_Shuteras_Verde_CAB_109.90_Derecha.webp';
}

function carga_segmentos_esperados(): array
{
    return ['categoria', 'marca', 'modelo', 'tipo', 'color', 'serie', 'precio', 'vista'];
}

function carga_normalizar_categoria(string $texto): string
{
    $mapa = [
        'zapatilla' => 'zapatillas',
        'zapatillas' => 'zapatillas',
        'zapato' => 'zapatillas',
        'bota' => 'zapatillas',
        'sandalia' => 'zapatillas',
    ];
    $clave = strtolower(trim($texto));
    $clave = preg_replace('/[^a-z0-9]/', '', $clave) ?? $clave;

    return $mapa[$clave] ?? categoria_normalizar($clave);
}

function carga_parsear_nombre_completo(string $nombreArchivo): array
{
    $base = pathinfo($nombreArchivo, PATHINFO_FILENAME);
    $partes = explode('_', $base);
    $esperados = count(carga_segmentos_esperados());

    if (count($partes) !== $esperados) {
        return [
            'ok' => false,
            'error' => 'El archivo de referencia «' . $nombreArchivo . '» debe tener exactamente ' . $esperados
                . ' segmentos separados por guion bajo. Formato: '
                . carga_nomenclatura_ejemplo(),
        ];
    }

    [$categoria, $marca, $modelo, $tipo, $color, $serieCodigo, $precioRaw, $vistaRaw] = $partes;

    if (trim($categoria) === '') {
        return ['ok' => false, 'error' => 'El segmento «Categoría» está vacío en «' . $nombreArchivo . '».'];
    }
    if (trim($marca) === '') {
        return ['ok' => false, 'error' => 'El segmento «Marca» está vacío en «' . $nombreArchivo . '».'];
    }
    if (trim($modelo) === '') {
        return ['ok' => false, 'error' => 'El segmento «Modelo» está vacío en «' . $nombreArchivo . '».'];
    }
    if (trim($tipo) === '') {
        return ['ok' => false, 'error' => 'El segmento «Tipo» está vacío en «' . $nombreArchivo . '».'];
    }
    if (trim($color) === '') {
        return ['ok' => false, 'error' => 'El segmento «Color» está vacío en «' . $nombreArchivo . '».'];
    }
    if (trim($serieCodigo) === '') {
        return ['ok' => false, 'error' => 'El segmento «Serie de tallas» está vacío en «' . $nombreArchivo . '».'];
    }

    $serie = series_obtener_por_codigo_corto($serieCodigo);
    if ($serie === null) {
        return [
            'ok' => false,
            'error' => 'El código de serie «' . $serieCodigo . '» en «' . $nombreArchivo
                . '» no existe. Use un código válido (ej. CAB, DAM, JUN).',
        ];
    }

    $precioRaw = str_replace(',', '.', trim($precioRaw));
    if (!is_numeric($precioRaw) || (float) $precioRaw <= 0) {
        return [
            'ok' => false,
            'error' => 'El segmento «Precio» («' . $precioRaw . '») en «' . $nombreArchivo
                . '» no es válido. Use un número mayor a 0 (ej. 109.90).',
        ];
    }

    $vista = strtolower(trim($vistaRaw));
    $vistaNormalizada = carga_vista_desde_texto($vista);
    if ($vistaNormalizada === null) {
        return [
            'ok' => false,
            'error' => 'El segmento «Vista» («' . $vistaRaw . '») en «' . $nombreArchivo
                . '» no es válido. El archivo completo debe terminar en Derecha.',
        ];
    }

    if ($vistaNormalizada !== 'derecha') {
        return [
            'ok' => false,
            'error' => 'El archivo con nomenclatura completa debe ser la vista Derecha. '
                . 'En «' . $nombreArchivo . '» la vista indicada es «' . $vistaRaw . '».',
        ];
    }

    $categoriaSlug = carga_normalizar_categoria($categoria);
    if (!categoria_es_valida($categoriaSlug)) {
        return [
            'ok' => false,
            'error' => 'La categoría «' . $categoria . '» en «' . $nombreArchivo
                . '» no está registrada en la tienda. Revise el segmento Categoría.',
        ];
    }

    return [
        'ok' => true,
        'datos' => [
            'categoria_texto' => $categoria,
            'categoria' => $categoriaSlug,
            'marca' => trim($marca),
            'modelo' => trim($modelo),
            'tipo' => trim($tipo),
            'color' => trim($color),
            'serie_codigo' => series_normalizar_codigo_corto($serieCodigo),
            'serie_slug' => $serie['slug'],
            'serie_nombre' => $serie['nombre'],
            'precio' => round((float) $precioRaw, 2),
            'vista' => $vistaNormalizada,
            'nombre_archivo' => $nombreArchivo,
        ],
    ];
}

function carga_vista_desde_texto(string $texto): ?string
{
    $texto = strtolower(trim($texto));
    if ($texto === '') {
        return null;
    }

    foreach (imagenes_vistas() as $vista) {
        if ($texto === $vista || str_contains($texto, $vista)) {
            return $vista;
        }
    }

    return imagenes_vista_desde_nombre_archivo($texto);
}

function carga_parsear_nombre_vista_sola(string $nombreArchivo): array
{
    $base = pathinfo($nombreArchivo, PATHINFO_FILENAME);
    if (str_contains($base, '_')) {
        return [
            'ok' => false,
            'error' => 'El archivo «' . $nombreArchivo . '» solo debe indicar la vista '
                . '(ej. Izquierda.webp, Frente.webp). La nomenclatura completa va únicamente en Derecha.',
        ];
    }

    $vistaNormalizada = carga_vista_desde_texto($base);
    if ($vistaNormalizada === null) {
        return [
            'ok' => false,
            'error' => 'El archivo «' . $nombreArchivo . '» no indica una vista válida. '
                . 'Use: Izquierda, Frente, Posterior, Arriba o Abajo.',
        ];
    }

    if ($vistaNormalizada === 'derecha') {
        return [
            'ok' => false,
            'error' => 'La vista Derecha debe usar la nomenclatura completa: '
                . carga_nomenclatura_ejemplo(),
        ];
    }

    return [
        'ok' => true,
        'vista' => $vistaNormalizada,
        'nombre_archivo' => $nombreArchivo,
    ];
}

/** @deprecated Use carga_parsear_nombre_completo or carga_parsear_nombre_vista_sola */
function carga_parsear_nombre_archivo(string $nombreArchivo): array
{
    $base = pathinfo($nombreArchivo, PATHINFO_FILENAME);
    if (substr_count($base, '_') >= 7) {
        return carga_parsear_nombre_completo($nombreArchivo);
    }

    $simple = carga_parsear_nombre_vista_sola($nombreArchivo);
    if (!$simple['ok']) {
        return $simple;
    }

    return [
        'ok' => false,
        'error' => 'Falta el archivo Derecha con nomenclatura completa. Formato: '
            . carga_nomenclatura_ejemplo(),
    ];
}

/**
 * @param array<string, mixed> $files $_FILES['imagenes']
 */
function carga_procesar_upload(array $files, string $tipoLote, string $tipoMedia): array
{
    $nombres = $files['name'] ?? null;
    if (!is_array($nombres) || $nombres === []) {
        return ['ok' => false, 'error' => 'Debe seleccionar las 6 imágenes del producto.'];
    }

    if (count($nombres) !== 6) {
        return [
            'ok' => false,
            'error' => 'Debe subir exactamente 6 imágenes (una por vista). Recibidas: ' . count($nombres) . '.',
        ];
    }

    $referencia = null;
    $vistasRecibidas = [];
    $archivosPorIndice = [];

    foreach ($nombres as $i => $nombre) {
        $nombre = (string) $nombre;
        $base = pathinfo($nombre, PATHINFO_FILENAME);
        $esCompleto = substr_count($base, '_') >= 7;

        if ($esCompleto) {
            $resultado = carga_parsear_nombre_completo($nombre);
            if (!$resultado['ok']) {
                return $resultado;
            }
            if ($referencia !== null) {
                return [
                    'ok' => false,
                    'error' => 'Solo debe haber un archivo con nomenclatura completa (Derecha). '
                        . 'Revise «' . $nombre . '» y «' . $referencia['nombre_archivo'] . '».',
                ];
            }
            $referencia = $resultado['datos'];
            $vista = $referencia['vista'];
        } else {
            $resultado = carga_parsear_nombre_vista_sola($nombre);
            if (!$resultado['ok']) {
                return $resultado;
            }
            $vista = $resultado['vista'];
        }

        if (isset($vistasRecibidas[$vista])) {
            return [
                'ok' => false,
                'error' => 'Hay más de un archivo para la vista «' . $vista . '»: «'
                    . $vistasRecibidas[$vista] . '» y «' . $nombre . '».',
            ];
        }
        $vistasRecibidas[$vista] = $nombre;
        $archivosPorIndice[$i] = ['nombre' => $nombre, 'vista' => $vista];
    }

    if ($referencia === null) {
        return [
            'ok' => false,
            'error' => 'Falta el archivo Derecha con nomenclatura completa. Formato: '
                . carga_nomenclatura_ejemplo(),
        ];
    }

    $faltantes = array_diff(imagenes_vistas(), array_keys($vistasRecibidas));
    if ($faltantes !== []) {
        return [
            'ok' => false,
            'error' => 'Faltan vistas obligatorias: ' . implode(', ', $faltantes) . '. '
                . 'Las demás pueden nombrarse solo con la vista (ej. Izquierda.webp).',
        ];
    }

    $distribucion = series_distribucion($referencia['serie_codigo'], $tipoLote, $tipoMedia);
    if ($distribucion === []) {
        return [
            'ok' => false,
            'error' => 'No se encontró curva de stock para la serie «' . $referencia['serie_codigo']
                . '» y el tipo de lote seleccionado.',
        ];
    }

    $duplicado = carga_buscar_producto_duplicado($referencia);
    if ($duplicado !== null) {
        return [
            'ok' => false,
            'error' => carga_mensaje_producto_duplicado($duplicado),
            'duplicado_id' => (int) $duplicado['id'],
        ];
    }

    $token = bin2hex(random_bytes(16));
    $tmpDir = carga_tmp_dir() . '/' . $token;
    if (!mkdir($tmpDir, 0755, true) && !is_dir($tmpDir)) {
        return ['ok' => false, 'error' => 'No se pudo crear carpeta temporal para las imágenes.'];
    }

    $archivosPorVista = [];
    $tmpNames = $files['tmp_name'] ?? [];
    $errors = $files['error'] ?? [];

    foreach ($nombres as $i => $nombre) {
        $tmp = $tmpNames[$i] ?? '';
        $err = $errors[$i] ?? UPLOAD_ERR_NO_FILE;
        if ($err !== UPLOAD_ERR_OK || !is_uploaded_file($tmp)) {
            carga_limpiar_tmp($token);

            return ['ok' => false, 'error' => 'Error al subir «' . $nombre . '».'];
        }

        $vista = $archivosPorIndice[$i]['vista'];
        $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
        if (!in_array($ext, ['webp', 'jpg', 'jpeg', 'png'], true)) {
            carga_limpiar_tmp($token);

            return ['ok' => false, 'error' => 'Formato no permitido en «' . $nombre . '». Use WEBP, JPG o PNG.'];
        }

        $destino = $tmpDir . '/' . $vista . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
        if (!move_uploaded_file($tmp, $destino)) {
            carga_limpiar_tmp($token);

            return ['ok' => false, 'error' => 'No se pudo guardar temporalmente «' . $nombre . '».'];
        }
        $archivosPorVista[$vista] = $destino;
    }

    return [
        'ok' => true,
        'token' => $token,
        'datos' => $referencia,
        'distribucion' => $distribucion,
        'archivos_tmp' => $archivosPorVista,
        'tipo_lote' => $tipoLote,
        'tipo_media' => $tipoMedia,
    ];
}

function carga_tmp_dir(): string
{
    $dir = dirname(__DIR__) . '/tmp/carga_individual';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    return $dir;
}

function carga_limpiar_tmp(string $token): void
{
    $dir = carga_tmp_dir() . '/' . preg_replace('/[^a-f0-9]/', '', $token);
    if (!is_dir($dir)) {
        return;
    }

    foreach (glob($dir . '/*') ?: [] as $archivo) {
        if (is_file($archivo)) {
            unlink($archivo);
        }
    }
    rmdir($dir);
}

function carga_normalizar_comparacion(string $texto): string
{
    $texto = mb_strtolower(trim($texto), 'UTF-8');
    if ($texto === '') {
        return '';
    }

    return preg_replace('/\s+/u', ' ', $texto) ?? $texto;
}

/**
 * @param array<string, mixed> $datos
 * @return array<string, mixed>|null
 */
function carga_buscar_producto_duplicado(array $datos): ?array
{
    require_once __DIR__ . '/db.php';
    require_once __DIR__ . '/repositorio_series.php';
    require_once __DIR__ . '/repositorio_productos.php';

    $categoria = categoria_normalizar((string) ($datos['categoria'] ?? ''));
    $marca = carga_normalizar_comparacion((string) ($datos['marca'] ?? ''));
    $modelo = carga_normalizar_comparacion((string) ($datos['modelo'] ?? ''));
    $tipo = carga_normalizar_comparacion((string) ($datos['tipo'] ?? ''));
    $color = carga_normalizar_comparacion((string) ($datos['color'] ?? ''));
    $serieSlug = series_resolver_slug_producto((string) ($datos['serie_slug'] ?? $datos['serie_codigo'] ?? ''));

    if ($marca === '' || $modelo === '' || $color === '' || !categoria_es_valida($categoria)) {
        return null;
    }

    $pdo = db();
    $stmt = $pdo->prepare(
        'SELECT p.id, p.marca, p.nombre, p.modelo, p.tipo, p.categoria, p.serie, pc.etiqueta AS color_etiqueta
         FROM productos p
         INNER JOIN producto_colores pc ON pc.id = (
             SELECT MIN(pc2.id) FROM producto_colores pc2 WHERE pc2.producto_id = p.id
         )
         WHERE p.categoria = :categoria
           AND LOWER(TRIM(p.marca)) = :marca
           AND LOWER(TRIM(COALESCE(NULLIF(p.modelo, \'\'), p.nombre))) = :modelo
           AND LOWER(TRIM(COALESCE(p.tipo, \'\'))) = :tipo
           AND LOWER(TRIM(pc.etiqueta)) = :color'
    );
    $stmt->execute([
        'categoria' => $categoria,
        'marca' => $marca,
        'modelo' => $modelo,
        'tipo' => $tipo,
        'color' => $color,
    ]);

    foreach ($stmt->fetchAll() as $fila) {
        if (series_resolver_slug_producto((string) ($fila['serie'] ?? '')) !== $serieSlug) {
            continue;
        }

        return [
            'id' => (int) $fila['id'],
            'marca' => (string) $fila['marca'],
            'modelo' => (string) ($fila['modelo'] ?: $fila['nombre']),
            'tipo' => (string) ($fila['tipo'] ?? ''),
            'categoria' => (string) $fila['categoria'],
            'color_etiqueta' => (string) ($fila['color_etiqueta'] ?? ''),
            'titulo_tienda' => producto_titulo_tienda([
                'categoria' => $fila['categoria'],
                'modelo' => $fila['modelo'] ?: $fila['nombre'],
                'tipo' => $fila['tipo'] ?? '',
                'nombre' => $fila['nombre'],
            ]),
        ];
    }

    return null;
}

/**
 * @param array<string, mixed> $existente
 */
function carga_mensaje_producto_duplicado(array $existente): string
{
    $id = (int) ($existente['id'] ?? 0);
    $titulo = trim((string) ($existente['titulo_tienda'] ?? ''));
    if ($titulo === '') {
        $titulo = trim(implode(' · ', array_filter([
            (string) ($existente['marca'] ?? ''),
            (string) ($existente['modelo'] ?? ''),
            (string) ($existente['color_etiqueta'] ?? ''),
        ])));
    }

    $detalle = $titulo !== '' ? ' («' . $titulo . '»)' : '';

    return 'Este producto ya fue cargado (ID ' . $id . $detalle . '). '
        . 'Si necesitas actualizar stock o datos, edítalo desde el listado de productos.';
}

function carga_color_codigo(string $etiqueta): string
{
    $base = strtoupper(preg_replace('/[^A-Z0-9]/', '', strtoupper($etiqueta)) ?? '');
    if ($base === '') {
        return 'C1';
    }

    return substr($base, 0, 6);
}

function carga_construir_producto(array $datos, array $distribucion, array $archivosTmp, string $publico, string $descripcion, array $tallasExtra = []): array
{
    $serie = series_obtener_por_codigo_corto($datos['serie_codigo']);
    if ($serie === null) {
        throw new RuntimeException('Serie no válida.');
    }

    $stockMapa = $distribucion;
    foreach ($tallasExtra as $extra) {
        $num = (string) ($extra['numero'] ?? '');
        $cant = max(0, (int) ($extra['cantidad'] ?? 0));
        if ($num === '' || $cant <= 0) {
            continue;
        }
        if (!series_validar_talla_en_serie($serie, $num, true)) {
            throw new RuntimeException('La talla especial «' . $num . '» no es válida para la serie ' . $serie['nombre'] . '.');
        }
        $stockMapa[$num] = ($stockMapa[$num] ?? 0) + $cant;
    }

    $tallas = [];
    foreach (array_keys($stockMapa) as $numero) {
        $tallas[] = [
            'numero' => (string) $numero,
            'disponible' => ((int) ($stockMapa[$numero] ?? 0)) > 0,
        ];
    }
    usort($tallas, static fn ($a, $b) => (int) $a['numero'] <=> (int) $b['numero']);

    $codigoColor = carga_color_codigo($datos['color']);
    $variantes = [];
    foreach ($stockMapa as $numero => $cantidad) {
        $variantes[(string) $numero] = ((int) $cantidad) > 0;
    }

    $imagenes = [];
    foreach ($archivosTmp as $vista => $ruta) {
        $imagenes[$vista] = $ruta;
    }

    return [
        'payload' => [
            'marca' => $datos['marca'],
            'nombre' => $datos['modelo'],
            'modelo' => $datos['modelo'],
            'tipo' => $datos['tipo'],
            'descripcion' => $descripcion,
            'categoria' => $datos['categoria'],
            'publico' => $publico,
            'precio' => $datos['precio'],
            'precio_anterior' => 0,
            'aplicar_descuento' => true,
            'serie' => $datos['serie_slug'],
            'color_default' => $codigoColor,
            'colores' => [[
                'codigo' => $codigoColor,
                'etiqueta' => $datos['color'],
                'imagen' => '',
                'imagenes' => array_fill_keys(imagenes_vistas(), ''),
                'alt' => $datos['marca'] . ' ' . $datos['modelo'],
                'sku_base' => strtoupper(substr(preg_replace('/[^A-Z0-9]/', '', strtoupper($datos['marca'])) ?? 'REF', 0, 3))
                    . '-' . strtoupper(substr(preg_replace('/[^A-Z0-9]/', '', strtoupper($datos['modelo'])) ?? 'MOD', 0, 8))
                    . '-' . $codigoColor . '-{talla}',
                'sku_sin_talla' => strtoupper(substr(preg_replace('/[^A-Z0-9]/', '', strtoupper($datos['marca'])) ?? 'REF', 0, 3))
                    . '-' . strtoupper(substr(preg_replace('/[^A-Z0-9]/', '', strtoupper($datos['modelo'])) ?? 'MOD', 0, 8))
                    . '-' . $codigoColor,
                'codigo_inventario' => '',
                'variantes' => $variantes,
            ]],
            'tallas' => $tallas,
        ],
        'stock_mapa' => $stockMapa,
        'imagenes_tmp' => $archivosTmp,
        'serie_slug' => $datos['serie_slug'],
    ];
}

function carga_mover_imagenes_producto(int $productoId, array $imagenesTmp): array
{
    $imagenes = array_fill_keys(imagenes_vistas(), '');

    foreach ($imagenesTmp as $vista => $rutaTmp) {
        if (!is_file($rutaTmp)) {
            continue;
        }
        $ext = strtolower(pathinfo($rutaTmp, PATHINFO_EXTENSION));
        $destinoFs = imagenes_ruta_destino($productoId, 0, $vista, $ext === 'jpeg' ? 'jpg' : $ext);
        if (!copy($rutaTmp, $destinoFs)) {
            throw new RuntimeException('No se pudo copiar la imagen ' . $vista . ' al producto.');
        }
        $imagenes[$vista] = imagenes_ruta_publica($productoId, 0, $vista, $ext === 'jpeg' ? 'jpg' : $ext);
    }

    return $imagenes;
}
