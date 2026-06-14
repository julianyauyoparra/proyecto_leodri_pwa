<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/imagenes_producto.php';
require_once __DIR__ . '/series_tallas.php';
require_once __DIR__ . '/categorias_tienda.php';
require_once __DIR__ . '/repositorio_series.php';

function db_migrar_quitar_activo_orden(): void
{
    $pdo = db();
    $stmt = $pdo->query("SHOW COLUMNS FROM productos LIKE 'activo'");
    if (!$stmt->fetch()) {
        return;
    }

    $indices = $pdo->query("SHOW INDEX FROM productos WHERE Key_name = 'idx_productos_activo_orden'")->fetchAll();
    if ($indices !== []) {
        $pdo->exec('ALTER TABLE productos DROP INDEX idx_productos_activo_orden');
    }

    $pdo->exec('ALTER TABLE productos DROP COLUMN activo, DROP COLUMN orden');
}

function db_migrar_quitar_beneficios_bullets(): void
{
    static $migrado = false;
    if ($migrado) {
        return;
    }

    $pdo = db();
    $stmtTabla = $pdo->query("SHOW TABLES LIKE 'producto_beneficios'");
    $tieneBeneficios = (bool) $stmtTabla->fetch();
    $stmtColumna = $pdo->query("SHOW COLUMNS FROM productos LIKE 'bullets'");
    $tieneBullets = (bool) $stmtColumna->fetch();

    if (!$tieneBeneficios && !$tieneBullets) {
        $migrado = true;

        return;
    }

    if ($tieneBullets || $tieneBeneficios) {
        $stmtProductos = $pdo->query(
            $tieneBullets
                ? 'SELECT id, descripcion, bullets FROM productos'
                : 'SELECT id, descripcion FROM productos'
        );
        $stmtActualizar = $pdo->prepare('UPDATE productos SET descripcion = :descripcion WHERE id = :id');
        $stmtBeneficios = $tieneBeneficios
            ? $pdo->prepare(
                'SELECT titulo, texto FROM producto_beneficios
                 WHERE producto_id = :id ORDER BY orden ASC, id ASC'
            )
            : null;

        foreach ($stmtProductos->fetchAll() as $fila) {
            $partes = [];
            $descripcion = trim((string) ($fila['descripcion'] ?? ''));

            if ($tieneBullets) {
                $bullets = json_decode((string) ($fila['bullets'] ?? '[]'), true);
                if (is_array($bullets)) {
                    foreach ($bullets as $bullet) {
                        $texto = trim((string) $bullet);
                        if ($texto !== '') {
                            $partes[] = $texto;
                        }
                    }
                }
            }

            if ($stmtBeneficios !== null) {
                $stmtBeneficios->execute(['id' => (int) $fila['id']]);
                foreach ($stmtBeneficios->fetchAll() as $beneficio) {
                    $titulo = trim((string) ($beneficio['titulo'] ?? ''));
                    $texto = trim((string) ($beneficio['texto'] ?? ''));
                    if ($titulo === '' && $texto === '') {
                        continue;
                    }
                    $partes[] = $titulo !== '' && $texto !== ''
                        ? $titulo . ': ' . $texto
                        : ($titulo !== '' ? $titulo : $texto);
                }
            }

            if ($partes === []) {
                continue;
            }

            $extra = implode("\n", $partes);
            $nuevaDescripcion = $descripcion !== '' ? $descripcion . "\n\n" . $extra : $extra;
            $stmtActualizar->execute([
                'descripcion' => $nuevaDescripcion,
                'id' => (int) $fila['id'],
            ]);
        }
    }

    if ($tieneBeneficios) {
        $pdo->exec('DROP TABLE IF EXISTS producto_beneficios');
    }

    if ($tieneBullets) {
        $pdo->exec('ALTER TABLE productos DROP COLUMN bullets');
    }

    $migrado = true;
}

function db_migrar_quitar_tags(): void
{
    static $migrado = false;
    if ($migrado) {
        return;
    }

    $pdo = db();
    $stmt = $pdo->query("SHOW COLUMNS FROM productos LIKE 'tags'");
    if (!$stmt->fetch()) {
        $migrado = true;

        return;
    }

    $pdo->exec('ALTER TABLE productos DROP COLUMN tags');
    $migrado = true;
}

function db_drop_foreign_key_if_exists(string $tabla, string $nombreFk): void
{
    $pdo = db();
    $stmt = $pdo->prepare(
        'SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
         WHERE CONSTRAINT_SCHEMA = DATABASE()
           AND TABLE_NAME = ?
           AND CONSTRAINT_NAME = ?
           AND CONSTRAINT_TYPE = \'FOREIGN KEY\''
    );
    $stmt->execute([$tabla, $nombreFk]);
    if ($stmt->fetch()) {
        $pdo->exec('ALTER TABLE `' . str_replace('`', '', $tabla) . '` DROP FOREIGN KEY `' . str_replace('`', '', $nombreFk) . '`');
    }
}

function db_migrar_pedidos_fk_set_null(): void
{
    static $migrado = false;
    if ($migrado) {
        return;
    }

    $pdo = db();
    $stmtTabla = $pdo->query("SHOW TABLES LIKE 'pedidos'");
    if (!$stmtTabla->fetch()) {
        $migrado = true;

        return;
    }

    $ajustes = [
        'inventario_variante_id' => ['fk_pedidos_variante', 'inventario_variantes', 'id'],
        'producto_id' => ['fk_pedidos_producto', 'productos', 'id'],
        'producto_color_id' => ['fk_pedidos_color', 'producto_colores', 'id'],
        'producto_talla_id' => ['fk_pedidos_talla', 'producto_tallas', 'id'],
    ];

    foreach ($ajustes as $columna => [$nombreFk, $tablaRef, $colRef]) {
        $stmtRegla = $pdo->prepare(
            'SELECT DELETE_RULE FROM information_schema.REFERENTIAL_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE()
               AND TABLE_NAME = \'pedidos\'
               AND CONSTRAINT_NAME = ?'
        );
        $stmtRegla->execute([$nombreFk]);
        $regla = $stmtRegla->fetchColumn();

        $stmtColumna = $pdo->query('SHOW COLUMNS FROM pedidos LIKE ' . $pdo->quote($columna));
        $columnaInfo = $stmtColumna->fetch();
        $esNullable = $columnaInfo && strtoupper((string) ($columnaInfo['Null'] ?? '')) === 'YES';

        if ($regla === 'SET NULL' && $esNullable) {
            continue;
        }

        db_drop_foreign_key_if_exists('pedidos', $nombreFk);

        if ($columnaInfo && !$esNullable) {
            $pdo->exec('ALTER TABLE pedidos MODIFY `' . $columna . '` INT UNSIGNED NULL');
        }

        if ($regla !== 'SET NULL') {
            $pdo->exec(
                'ALTER TABLE pedidos ADD CONSTRAINT `' . $nombreFk . '`
                 FOREIGN KEY (`' . $columna . '`) REFERENCES `' . $tablaRef . '` (`' . $colRef . '`)
                 ON DELETE SET NULL'
            );
        }
    }

    $migrado = true;
}

function db_migrar_precio_serie(): void
{
    $pdo = db();
    $columnas = [
        'precio_anterior' => 'DECIMAL(10, 2) NOT NULL DEFAULT 0 AFTER precio',
        'aplicar_descuento' => 'TINYINT(1) NOT NULL DEFAULT 1 AFTER precio_anterior',
        'serie' => "VARCHAR(30) NOT NULL DEFAULT 'escolar' AFTER aplicar_descuento",
    ];

    foreach ($columnas as $nombre => $definicion) {
        $stmt = $pdo->query("SHOW COLUMNS FROM productos LIKE " . $pdo->quote($nombre));
        if ($stmt->fetch()) {
            continue;
        }
        $pdo->exec('ALTER TABLE productos ADD COLUMN ' . $nombre . ' ' . $definicion);
    }

    $pdo->exec(
        'UPDATE productos SET precio_anterior = ROUND(precio * 1.27, 2)
         WHERE precio > 0 AND precio_anterior <= 0'
    );
}

function db_migrar_categoria(): void
{
    $pdo = db();
    $stmt = $pdo->query("SHOW COLUMNS FROM productos LIKE 'categoria'");
    if (!$stmt->fetch()) {
        $pdo->exec(
            "ALTER TABLE productos ADD COLUMN categoria VARCHAR(50) NOT NULL DEFAULT 'zapatillas' AFTER descripcion"
        );
    }

    $pdo->exec("UPDATE productos SET categoria = 'zapatillas' WHERE categoria = ''");
}

function productos_listar_por_categoria(string $categoria): array
{
    db_migrar_categoria();
    db_migrar_quitar_activo_orden();
    db_migrar_quitar_beneficios_bullets();
    db_migrar_quitar_tags();

    $categoria = categoria_normalizar($categoria);
    if (!categoria_es_valida($categoria)) {
        $categoria = CATEGORIA_HOME_DEFAULT;
    }

    $pdo = db();
    $stmt = $pdo->prepare(
        'SELECT * FROM productos
         WHERE categoria = :categoria
         ORDER BY id DESC'
    );
    $stmt->execute(['categoria' => $categoria]);

    $productos = [];
    foreach ($stmt->fetchAll() as $fila) {
        $productoId = (int) $fila['id'];
        if (!producto_tiene_foto_tienda($productoId)) {
            continue;
        }

        [$colores, $tallas, $variantes] = producto_cargar_relaciones($productoId);
        if ($colores === []) {
            continue;
        }
        $colores = producto_aplicar_variantes_a_colores($colores, $tallas, $variantes, $productoId);
        $productos[] = producto_formatear_tienda($fila, $colores, $tallas, $variantes);
    }

    return $productos;
}

function db_migrar_variantes(): void
{
    $pdo = db();
    $stmt = $pdo->query("SHOW TABLES LIKE 'producto_variantes'");
    if (!$stmt->fetch()) {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS producto_variantes (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                producto_id INT UNSIGNED NOT NULL,
                color_codigo VARCHAR(20) NOT NULL,
                talla_numero VARCHAR(10) NOT NULL,
                disponible TINYINT(1) NOT NULL DEFAULT 1,
                CONSTRAINT fk_variantes_producto
                    FOREIGN KEY (producto_id) REFERENCES productos (id)
                    ON DELETE CASCADE,
                UNIQUE KEY uk_variante (producto_id, color_codigo, talla_numero),
                INDEX idx_variantes_producto (producto_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    $stmt = $pdo->query(
        'SELECT p.id AS producto_id, pc.codigo AS color_codigo, pt.numero AS talla_numero, pt.disponible
         FROM productos p
         INNER JOIN producto_colores pc ON pc.producto_id = p.id
         INNER JOIN producto_tallas pt ON pt.producto_id = p.id
         LEFT JOIN producto_variantes pv
            ON pv.producto_id = p.id AND pv.color_codigo = pc.codigo AND pv.talla_numero = pt.numero
         WHERE pv.id IS NULL'
    );
    $faltantes = $stmt->fetchAll();
    if ($faltantes === []) {
        return;
    }

    $insert = $pdo->prepare(
        'INSERT INTO producto_variantes (producto_id, color_codigo, talla_numero, disponible)
         VALUES (:producto_id, :color_codigo, :talla_numero, :disponible)'
    );
    foreach ($faltantes as $fila) {
        $insert->execute([
            'producto_id' => (int) $fila['producto_id'],
            'color_codigo' => $fila['color_codigo'],
            'talla_numero' => $fila['talla_numero'],
            'disponible' => (int) $fila['disponible'],
        ]);
    }
}

function db_tiene_inventario_variantes(): bool
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $pdo = db();
    $stmt = $pdo->query("SHOW TABLES LIKE 'inventario_variantes'");
    $cache = (bool) $stmt->fetch();

    return $cache;
}

function inventario_sku_desde_color_talla(string $skuBase, string $tallaNumero): string
{
    return str_replace('{talla}', $tallaNumero, $skuBase);
}

function producto_cargar_variantes_legacy(int $productoId): array
{
    $pdo = db();
    $mapa = [];
    $stmt = $pdo->prepare(
        'SELECT color_codigo, talla_numero, disponible
         FROM producto_variantes WHERE producto_id = :id'
    );
    $stmt->execute(['id' => $productoId]);

    foreach ($stmt->fetchAll() as $fila) {
        $codigo = $fila['color_codigo'];
        if (!isset($mapa[$codigo])) {
            $mapa[$codigo] = [];
        }
        $mapa[$codigo][(string) $fila['talla_numero']] = (bool) $fila['disponible'];
    }

    return $mapa;
}

function producto_cargar_variantes_inventario(int $productoId): array
{
    $pdo = db();
    $mapa = [];
    $stmt = $pdo->prepare(
        'SELECT pc.codigo AS color_codigo, pt.numero AS talla_numero,
                IF((iv.stock - iv.stock_reservado) > 0, 1, 0) AS disponible
         FROM inventario_variantes iv
         INNER JOIN producto_colores pc ON pc.id = iv.producto_color_id
         INNER JOIN producto_tallas pt ON pt.id = iv.producto_talla_id
         WHERE iv.producto_id = :id'
    );
    $stmt->execute(['id' => $productoId]);

    foreach ($stmt->fetchAll() as $fila) {
        $codigo = $fila['color_codigo'];
        if (!isset($mapa[$codigo])) {
            $mapa[$codigo] = [];
        }
        $mapa[$codigo][(string) $fila['talla_numero']] = (bool) $fila['disponible'];
    }

    return $mapa;
}

function producto_cargar_variantes(int $productoId): array
{
    $legacy = producto_cargar_variantes_legacy($productoId);

    if (!db_tiene_inventario_variantes()) {
        return $legacy;
    }

    $inventario = producto_cargar_variantes_inventario($productoId);

    if ($inventario === []) {
        return $legacy;
    }

    $mapa = $legacy;

    foreach ($inventario as $codigo => $tallas) {
        if (!isset($mapa[$codigo])) {
            $mapa[$codigo] = [];
        }
        foreach ($tallas as $numero => $disponible) {
            $mapa[$codigo][$numero] = $disponible;
        }
    }

    return $mapa;
}

function inventario_preservar_stock_por_sku(int $productoId): array
{
    if (!db_tiene_inventario_variantes()) {
        return [];
    }

    $pdo = db();
    $stmt = $pdo->prepare(
        'SELECT sku, stock, stock_reservado FROM inventario_variantes WHERE producto_id = :id'
    );
    $stmt->execute(['id' => $productoId]);
    $preservado = [];

    foreach ($stmt->fetchAll() as $fila) {
        $preservado[(string) $fila['sku']] = [
            'stock' => (int) $fila['stock'],
            'stock_reservado' => (int) $fila['stock_reservado'],
        ];
    }

    return $preservado;
}

function inventario_reparar_desync_vendidos(): void
{
    if (!db_tiene_inventario_variantes()) {
        return;
    }

    $pdo = db();
    $pdo->exec(
        'UPDATE inventario_variantes iv
         INNER JOIN producto_colores pc ON pc.id = iv.producto_color_id
         INNER JOIN producto_tallas pt ON pt.id = iv.producto_talla_id
         INNER JOIN producto_variantes pv
            ON pv.producto_id = iv.producto_id
           AND pv.color_codigo = pc.codigo
           AND pv.talla_numero = pt.numero
         SET iv.stock = 0, iv.stock_reservado = 0
         WHERE pv.disponible = 0 AND (iv.stock > 0 OR iv.stock_reservado > 0)'
    );
}

function inventario_sincronizar_productos_pendientes(): void
{
    if (!db_tiene_inventario_variantes()) {
        return;
    }

    inventario_reparar_desync_vendidos();

    $pdo = db();
    $stmt = $pdo->query(
        'SELECT p.id
         FROM productos p
         INNER JOIN producto_colores pc ON pc.producto_id = p.id
         INNER JOIN producto_tallas pt ON pt.producto_id = p.id
         LEFT JOIN inventario_variantes iv
            ON iv.producto_id = p.id
           AND iv.producto_color_id = pc.id
           AND iv.producto_talla_id = pt.id
         WHERE iv.id IS NULL
         GROUP BY p.id'
    );

    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $productoId) {
        inventario_sincronizar_producto((int) $productoId);
    }
}

function inventario_sincronizar_producto(int $productoId, array $preservadoPorSku = []): void
{
    if (!db_tiene_inventario_variantes()) {
        return;
    }

    $pdo = db();
    $stmtColores = $pdo->prepare(
        'SELECT id, codigo, sku_base FROM producto_colores WHERE producto_id = :id ORDER BY orden ASC, id ASC'
    );
    $stmtColores->execute(['id' => $productoId]);
    $colores = $stmtColores->fetchAll();

    $stmtTallas = $pdo->prepare(
        'SELECT id, numero FROM producto_tallas WHERE producto_id = :id ORDER BY orden ASC, id ASC'
    );
    $stmtTallas->execute(['id' => $productoId]);
    $tallas = $stmtTallas->fetchAll();

    $variantes = producto_cargar_variantes_legacy($productoId);

    $insert = $pdo->prepare(
        'INSERT INTO inventario_variantes
            (producto_id, producto_color_id, producto_talla_id, sku, stock, stock_reservado)
         VALUES (:producto_id, :producto_color_id, :producto_talla_id, :sku, :stock, :stock_reservado)
         ON DUPLICATE KEY UPDATE
            producto_color_id = VALUES(producto_color_id),
            producto_talla_id = VALUES(producto_talla_id),
            stock = VALUES(stock),
            stock_reservado = VALUES(stock_reservado)'
    );

    foreach ($colores as $color) {
        foreach ($tallas as $talla) {
            $numero = (string) $talla['numero'];
            $codigo = (string) $color['codigo'];
            $sku = inventario_sku_desde_color_talla((string) $color['sku_base'], $numero);

            if (isset($preservadoPorSku[$sku])) {
                $stock = $preservadoPorSku[$sku]['stock'];
                $reservado = $preservadoPorSku[$sku]['stock_reservado'];
            } else {
                $disponible = $variantes[$codigo][$numero] ?? true;
                $stock = $disponible ? 1 : 0;
                $reservado = 0;
            }

            $insert->execute([
                'producto_id' => $productoId,
                'producto_color_id' => (int) $color['id'],
                'producto_talla_id' => (int) $talla['id'],
                'sku' => $sku,
                'stock' => max(0, $stock),
                'stock_reservado' => max(0, $reservado),
            ]);
        }
    }
}

function inventario_marcar_agotado(int $productoId, string $colorCodigo, string $tallaNumero): bool
{
    if (!db_tiene_inventario_variantes()) {
        return false;
    }

    $pdo = db();
    $stmt = $pdo->prepare(
        'UPDATE inventario_variantes iv
         INNER JOIN producto_colores pc ON pc.id = iv.producto_color_id
         INNER JOIN producto_tallas pt ON pt.id = iv.producto_talla_id
         SET iv.stock = 0, iv.stock_reservado = 0
         WHERE iv.producto_id = :producto_id
           AND pc.codigo = :color
           AND pt.numero = :talla
           AND (iv.stock > 0 OR iv.stock_reservado > 0)'
    );
    $stmt->execute([
        'producto_id' => $productoId,
        'color' => $colorCodigo,
        'talla' => $tallaNumero,
    ]);

    return $stmt->rowCount() > 0;
}

function inventario_variante_disponible(int $productoId, string $colorCodigo, string $tallaNumero): ?bool
{
    if (!db_tiene_inventario_variantes()) {
        return null;
    }

    $pdo = db();
    $stmt = $pdo->prepare(
        'SELECT iv.stock, iv.stock_reservado
         FROM inventario_variantes iv
         INNER JOIN producto_colores pc ON pc.id = iv.producto_color_id
         INNER JOIN producto_tallas pt ON pt.id = iv.producto_talla_id
         WHERE iv.producto_id = :producto_id
           AND pc.codigo = :color
           AND pt.numero = :talla
         LIMIT 1'
    );
    $stmt->execute([
        'producto_id' => $productoId,
        'color' => $colorCodigo,
        'talla' => $tallaNumero,
    ]);
    $fila = $stmt->fetch();

    if (!$fila) {
        return null;
    }

    return ((int) $fila['stock'] - (int) $fila['stock_reservado']) > 0;
}

function producto_aplicar_variantes_a_colores(array $colores, array $tallas, array $variantes, int $productoId = 0): array
{
    return array_map(static function (array $color) use ($tallas, $variantes, $productoId): array {
        $codigo = $color['codigo'] ?? '';
        $mapa = [];

        foreach ($tallas as $talla) {
            $numero = (string) $talla['numero'];
            $desdeInventario = $productoId > 0
                ? inventario_variante_disponible($productoId, $codigo, $numero)
                : null;

            if ($desdeInventario !== null) {
                $mapa[$numero] = $desdeInventario;
            } elseif (isset($variantes[$codigo][$numero])) {
                $mapa[$numero] = (bool) $variantes[$codigo][$numero];
            } else {
                $mapa[$numero] = (bool) $talla['disponible'];
            }
        }

        $color['tallas_disponibles'] = $mapa;
        $color['variantes'] = $mapa;

        return $color;
    }, $colores);
}

function producto_titulo_tienda(array $producto): string
{
    require_once __DIR__ . '/categorias_tienda.php';

    $partes = [
        categoria_etiqueta((string) ($producto['categoria'] ?? CATEGORIA_HOME_DEFAULT)),
        trim((string) ($producto['modelo'] ?? $producto['nombre'] ?? '')),
        trim((string) ($producto['tipo'] ?? '')),
    ];

    $partes = array_values(array_filter($partes, static fn (string $parte): bool => $parte !== ''));

    return implode(' ', $partes);
}

function producto_formatear_tienda(array $fila, array $colores, array $tallas, array $variantes = []): array
{
    require_once __DIR__ . '/repositorio_guias.php';

    $productoId = (int) $fila['id'];
    $serieSlug = series_resolver_slug_producto((string) ($fila['serie'] ?? 'escolar'));
    $guia = guia_obtener($productoId, $serieSlug);

    return [
        'id' => (string) $productoId,
        'marca' => $fila['marca'],
        'nombre' => $fila['nombre'],
        'modelo' => $fila['modelo'] ?? $fila['nombre'],
        'tipo' => $fila['tipo'] ?? '',
        'publico' => $fila['publico'] ?? 'unisex',
        'titulo_tienda' => producto_titulo_tienda([
            'categoria' => categoria_normalizar((string) ($fila['categoria'] ?? CATEGORIA_HOME_DEFAULT)),
            'modelo' => $fila['modelo'] ?? $fila['nombre'],
            'tipo' => $fila['tipo'] ?? '',
            'nombre' => $fila['nombre'],
        ]),
        'descripcion' => $fila['descripcion'],
        'categoria' => categoria_normalizar((string) ($fila['categoria'] ?? CATEGORIA_HOME_DEFAULT)),
        'precio' => (float) $fila['precio'],
        'precio_anterior' => (float) ($fila['precio_anterior'] ?? 0),
        'aplicar_descuento' => producto_leer_aplicar_descuento($fila),
        'serie' => $serieSlug,
        'guia_titulo' => $guia['titulo'] ?? '',
        'guia_pdf' => $guia ? trim((string) ($guia['archivo_pdf'] ?? '')) : '',
        'guia_html' => ($guia && trim((string) ($guia['archivo_pdf'] ?? '')) === '')
            ? guia_render_html($guia)
            : '',
        'descuento_pct' => producto_descuento_porcentaje(
            (float) $fila['precio'],
            (float) ($fila['precio_anterior'] ?? 0)
        ),
        'color_default' => $fila['color_default'],
        'colores' => array_map(static function (array $color): array {
            $color = imagenes_normalizar_color($color);
            $publico = [
                'codigo' => $color['codigo'],
                'etiqueta' => $color['etiqueta'],
                'imagen' => $color['imagen'],
                'imagenes' => $color['imagenes'],
                'alt' => $color['alt'],
                'sku_base' => $color['sku_base'],
                'sku_sin_talla' => $color['sku_sin_talla'],
            ];
            if (isset($color['tallas_disponibles'])) {
                $publico['tallas_disponibles'] = $color['tallas_disponibles'];
            }

            return $publico;
        }, $colores),
        'tallas' => array_map(static function (array $talla): array {
            return [
                'numero' => (string) $talla['numero'],
                'disponible' => (bool) $talla['disponible'],
            ];
        }, $tallas),
    ];
}

function producto_cargar_relaciones(int $productoId): array
{
    db_migrar_variantes();
    $pdo = db();

    $stmtColores = $pdo->prepare(
        'SELECT codigo, etiqueta, imagen, imagenes, alt, sku_base, sku_sin_talla
         FROM producto_colores WHERE producto_id = :id ORDER BY orden ASC, id ASC'
    );
    $stmtColores->execute(['id' => $productoId]);
    $colores = $stmtColores->fetchAll();

    $stmtTallas = $pdo->prepare(
        'SELECT numero, disponible FROM producto_tallas WHERE producto_id = :id ORDER BY orden ASC, id ASC'
    );
    $stmtTallas->execute(['id' => $productoId]);
    $tallas = $stmtTallas->fetchAll();

    return [$colores, $tallas, producto_cargar_variantes($productoId)];
}

function producto_inventario_stock_pares(int $productoId): array
{
    if (!db_tiene_inventario_variantes()) {
        return [];
    }

    $pdo = db();
    $stmt = $pdo->prepare(
        'SELECT pc.codigo AS color_codigo, pt.numero AS talla_numero,
                GREATEST(0, iv.stock - iv.stock_reservado) AS pares
         FROM inventario_variantes iv
         INNER JOIN producto_colores pc ON pc.id = iv.producto_color_id
         INNER JOIN producto_tallas pt ON pt.id = iv.producto_talla_id
         WHERE iv.producto_id = :id
         ORDER BY pc.orden ASC, pc.id ASC, pt.orden ASC, pt.id ASC'
    );
    $stmt->execute(['id' => $productoId]);
    $mapa = [];
    foreach ($stmt->fetchAll() as $fila) {
        $codigo = (string) $fila['color_codigo'];
        if (!isset($mapa[$codigo])) {
            $mapa[$codigo] = [];
        }
        $mapa[$codigo][(string) $fila['talla_numero']] = (int) $fila['pares'];
    }

    return $mapa;
}

function producto_obtener_admin(int $id): ?array
{
    db_migrar_quitar_beneficios_bullets();
    db_migrar_quitar_tags();
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM productos WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $fila = $stmt->fetch();

    if (!$fila) {
        return null;
    }

    [$colores, $tallas, $variantes] = producto_cargar_relaciones($id);
    $colores = producto_aplicar_variantes_a_colores($colores, $tallas, $variantes, $id);
    $producto = producto_formatear_tienda($fila, $colores, $tallas, $variantes);
    $producto['inventario_stock'] = producto_inventario_stock_pares($id);

    return $producto;
}

function productos_listar_admin(): array
{
    db_migrar_quitar_activo_orden();
    require_once __DIR__ . '/imagenes_producto.php';
    require_once __DIR__ . '/categorias_tienda.php';

    $pdo = db();
    $filas = $pdo->query(
        'SELECT p.id, p.marca, p.nombre, p.modelo, p.tipo, p.categoria, p.precio, p.actualizado_en,
                pc.imagen, pc.imagenes
         FROM productos p
         LEFT JOIN producto_colores pc ON pc.producto_id = p.id
           AND pc.id = (
               SELECT MIN(pc2.id) FROM producto_colores pc2 WHERE pc2.producto_id = p.id
           )
         ORDER BY p.id DESC'
    )->fetchAll();

    return array_map(static function (array $fila): array {
        $color = imagenes_normalizar_color([
            'imagen' => $fila['imagen'] ?? '',
            'imagenes' => $fila['imagenes'] ?? '',
        ]);
        $thumb = imagen_thumbnail($color);
        if ($thumb === '') {
            $thumb = $color['imagenes']['derecha'] ?? $color['imagen'] ?? '';
        }
        $tieneFoto = ($color['imagenes']['derecha'] ?? '') !== '' || ($color['imagen'] ?? '') !== '';

        return [
            'id' => (int) $fila['id'],
            'marca' => (string) $fila['marca'],
            'nombre' => (string) $fila['nombre'],
            'precio' => (float) $fila['precio'],
            'actualizado_en' => $fila['actualizado_en'],
            'categoria' => categoria_normalizar((string) ($fila['categoria'] ?? CATEGORIA_HOME_DEFAULT)),
            'titulo_tienda' => producto_titulo_tienda([
                'categoria' => $fila['categoria'] ?? '',
                'modelo' => $fila['modelo'] ?? $fila['nombre'],
                'tipo' => $fila['tipo'] ?? '',
                'nombre' => $fila['nombre'],
            ]),
            'imagen_thumb' => $thumb,
            'tiene_foto' => $tieneFoto,
        ];
    }, $filas);
}

function producto_url_en_home(int $id, string $categoria): string
{
    require_once __DIR__ . '/categorias_tienda.php';

    return 'home.php?categoria=' . rawurlencode(categoria_normalizar($categoria)) . '#producto-' . $id;
}

function producto_obtener(int $id): ?array
{
    db_migrar_quitar_beneficios_bullets();
    db_migrar_quitar_tags();
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM productos WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $fila = $stmt->fetch();

    if (!$fila) {
        return null;
    }

    [$colores, $tallas, $variantes] = producto_cargar_relaciones($id);
    $colores = producto_aplicar_variantes_a_colores($colores, $tallas, $variantes, $id);

    return producto_formatear_tienda($fila, $colores, $tallas, $variantes);
}

function producto_tiene_foto_tienda(int $productoId): bool
{
    $pdo = db();
    $stmt = $pdo->prepare(
        'SELECT imagen, imagenes FROM producto_colores WHERE producto_id = :id ORDER BY orden ASC, id ASC LIMIT 1'
    );
    $stmt->execute(['id' => $productoId]);
    $fila = $stmt->fetch();
    if (!$fila) {
        return false;
    }

    $color = imagenes_normalizar_color([
        'imagen' => $fila['imagen'],
        'imagenes' => $fila['imagenes'],
    ]);

    return ($color['imagenes']['derecha'] ?? '') !== '' || ($color['imagen'] ?? '') !== '';
}

function productos_listar_sin_foto(): array
{
    $pendientes = [];
    foreach (productos_listar_admin() as $fila) {
        if (!producto_tiene_foto_tienda((int) $fila['id'])) {
            $pendientes[] = $fila;
        }
    }

    return $pendientes;
}

function producto_guardar(?int $id, array $datos): int
{
    db_migrar_quitar_activo_orden();
    db_migrar_quitar_beneficios_bullets();
    db_migrar_quitar_tags();
    db_migrar_series_carga();
    db_migrar_variantes();

    $categoria = categoria_desde_request((string) ($datos['categoria'] ?? CATEGORIA_HOME_DEFAULT));
    $publico = publico_es_valido((string) ($datos['publico'] ?? 'unisex'))
        ? (string) $datos['publico']
        : 'unisex';
    $serieSlug = series_resolver_slug_producto((string) ($datos['serie'] ?? 'SERIE_JUVENIL'));

    $pdo = db();
    $pdo->beginTransaction();
    $productoId = 0;

    try {
        $stockPreservado = [];

        $precioAnterior = (float) ($datos['precio_anterior'] ?? 0);
        $precio = (float) ($datos['precio'] ?? 0);
        if ($precioAnterior <= 0 && $precio > 0) {
            $precioAnterior = producto_precio_anterior_sugerido($precio);
        }

        if ($id === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO productos (marca, nombre, modelo, tipo, descripcion, categoria, publico,
                 precio, precio_anterior, aplicar_descuento, serie, color_default)
                 VALUES (:marca, :nombre, :modelo, :tipo, :descripcion, :categoria, :publico,
                 :precio, :precio_anterior, :aplicar_descuento, :serie, :color_default)'
            );
            $stmt->execute([
                'marca' => $datos['marca'],
                'nombre' => $datos['nombre'],
                'modelo' => trim((string) ($datos['modelo'] ?? $datos['nombre'] ?? '')),
                'tipo' => trim((string) ($datos['tipo'] ?? '')),
                'descripcion' => $datos['descripcion'],
                'categoria' => $categoria,
                'publico' => $publico,
                'precio' => $precio,
                'precio_anterior' => $precioAnterior,
                'aplicar_descuento' => !empty($datos['aplicar_descuento']) ? 1 : 0,
                'serie' => $serieSlug,
                'color_default' => $datos['color_default'],
            ]);
            $productoId = (int) $pdo->lastInsertId();
        } else {
            $stmt = $pdo->prepare(
                'UPDATE productos SET marca = :marca, nombre = :nombre, modelo = :modelo, tipo = :tipo,
                 descripcion = :descripcion, categoria = :categoria,
                 publico = :publico, precio = :precio, precio_anterior = :precio_anterior,
                 aplicar_descuento = :aplicar_descuento, serie = :serie, color_default = :color_default
                 WHERE id = :id'
            );
            $stmt->execute([
                'id' => $id,
                'marca' => $datos['marca'],
                'nombre' => $datos['nombre'],
                'modelo' => trim((string) ($datos['modelo'] ?? $datos['nombre'] ?? '')),
                'tipo' => trim((string) ($datos['tipo'] ?? '')),
                'descripcion' => $datos['descripcion'],
                'categoria' => $categoria,
                'publico' => $publico,
                'precio' => $precio,
                'precio_anterior' => $precioAnterior,
                'aplicar_descuento' => !empty($datos['aplicar_descuento']) ? 1 : 0,
                'serie' => $serieSlug,
                'color_default' => $datos['color_default'],
            ]);
            $productoId = $id;
            $stockPreservado = inventario_preservar_stock_por_sku($productoId);

            $pdo->prepare('DELETE FROM producto_variantes WHERE producto_id = :id')->execute(['id' => $productoId]);
            $pdo->prepare('DELETE FROM producto_colores WHERE producto_id = :id')->execute(['id' => $productoId]);
            $pdo->prepare('DELETE FROM producto_tallas WHERE producto_id = :id')->execute(['id' => $productoId]);
        }

        $stmtColor = $pdo->prepare(
            'INSERT INTO producto_colores (producto_id, codigo, etiqueta, imagen, imagenes, alt, sku_base, sku_sin_talla, orden)
             VALUES (:producto_id, :codigo, :etiqueta, :imagen, :imagenes, :alt, :sku_base, :sku_sin_talla, :orden)'
        );
        foreach ($datos['colores'] as $i => $colorFila) {
            $colorNormalizado = imagenes_normalizar_color($colorFila);
            $imagenesJson = json_encode($colorNormalizado['imagenes'], JSON_UNESCAPED_UNICODE);
            $stmtColor->execute([
                'producto_id' => $productoId,
                'codigo' => $colorNormalizado['codigo'],
                'etiqueta' => $colorNormalizado['etiqueta'],
                'imagen' => $colorNormalizado['imagen'],
                'imagenes' => $imagenesJson,
                'alt' => $colorNormalizado['alt'],
                'sku_base' => $colorNormalizado['sku_base'],
                'sku_sin_talla' => $colorNormalizado['sku_sin_talla'],
                'orden' => $i,
            ]);
        }

        $stmtTalla = $pdo->prepare(
            'INSERT INTO producto_tallas (producto_id, numero, disponible, orden)
             VALUES (:producto_id, :numero, :disponible, :orden)'
        );
        foreach ($datos['tallas'] as $i => $talla) {
            $stmtTalla->execute([
                'producto_id' => $productoId,
                'numero' => $talla['numero'],
                'disponible' => !empty($talla['disponible']) ? 1 : 0,
                'orden' => $i,
            ]);
        }

        $stmtVariante = $pdo->prepare(
            'INSERT INTO producto_variantes (producto_id, color_codigo, talla_numero, disponible)
             VALUES (:producto_id, :color_codigo, :talla_numero, :disponible)'
        );
        foreach ($datos['colores'] as $colorFila) {
            $codigoColor = $colorFila['codigo'] ?? '';
            $variantesColor = $colorFila['variantes'] ?? [];
            foreach ($datos['tallas'] as $talla) {
                $numero = (string) $talla['numero'];
                $disponible = array_key_exists($numero, $variantesColor)
                    ? !empty($variantesColor[$numero])
                    : !empty($talla['disponible']);

                $stmtVariante->execute([
                    'producto_id' => $productoId,
                    'color_codigo' => $codigoColor,
                    'talla_numero' => $numero,
                    'disponible' => $disponible ? 1 : 0,
                ]);
            }
        }

        inventario_sincronizar_producto($productoId, $stockPreservado);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return $productoId;
}

function producto_eliminar(int $id): void
{
    db_migrar_pedidos_fk_set_null();

    $pdo = db();
    $stmt = $pdo->prepare('DELETE FROM productos WHERE id = :id');
    $stmt->execute(['id' => $id]);

    if ($stmt->rowCount() === 0) {
        throw new RuntimeException('Producto no encontrado.');
    }
}

function producto_importar_desde_array(array $producto, int $orden): int
{
    return producto_guardar(null, [
        'marca' => $producto['marca'] ?? '',
        'nombre' => $producto['nombre'] ?? '',
        'descripcion' => $producto['descripcion'] ?? '',
        'precio' => (float) ($producto['precio'] ?? 0),
        'precio_anterior' => (float) ($producto['precio_anterior'] ?? 0),
        'aplicar_descuento' => producto_leer_aplicar_descuento($producto),
        'serie' => series_normalizar((string) ($producto['serie'] ?? 'escolar')),
        'color_default' => $producto['color_default'] ?? '',
        'colores' => $producto['colores'] ?? [],
        'tallas' => $producto['tallas'] ?? [],
    ]);
}

function producto_resolver_variante_por_sku(string $sku): ?array
{
    $sku = trim($sku);
    if ($sku === '') {
        return null;
    }

    $pdo = db();
    $stmt = $pdo->query('SELECT producto_id, codigo, sku_base FROM producto_colores');
    foreach ($stmt->fetchAll() as $fila) {
        $base = (string) $fila['sku_base'];
        if (!str_contains($base, '{talla}')) {
            continue;
        }

        $prefijo = str_replace('{talla}', '', $base);
        if ($prefijo === '' || !str_starts_with($sku, $prefijo)) {
            continue;
        }

        $talla = substr($sku, strlen($prefijo));
        if ($talla === '') {
            continue;
        }

        return [
            'producto_id' => (int) $fila['producto_id'],
            'color' => (string) $fila['codigo'],
            'talla' => $talla,
            'sku' => $sku,
        ];
    }

    return null;
}

function producto_variante_confirmar_venta_por_sku(string $sku): array
{
    $variante = producto_resolver_variante_por_sku($sku);
    if ($variante === null) {
        return ['ok' => false, 'error' => 'SKU no encontrado'];
    }

    $resultado = producto_variante_marcar_vendida(
        $variante['producto_id'],
        $variante['color'],
        $variante['talla']
    );

    if (!empty($resultado['ok'])) {
        $resultado['sku'] = $variante['sku'];
        $resultado['producto_id'] = $variante['producto_id'];
        $resultado['color'] = $variante['color'];
        $resultado['talla'] = $variante['talla'];
    }

    return $resultado;
}

function producto_variante_marcar_vendida(int $productoId, string $colorCodigo, string $tallaNumero): array
{
    db_migrar_variantes();
    $pdo = db();

    $stmt = $pdo->prepare('SELECT id FROM productos WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $productoId]);
    if (!$stmt->fetch()) {
        return ['ok' => false, 'error' => 'Producto no disponible'];
    }

    $stmt = $pdo->prepare(
        'SELECT 1 FROM producto_colores WHERE producto_id = :id AND codigo = :color LIMIT 1'
    );
    $stmt->execute(['id' => $productoId, 'color' => $colorCodigo]);
    if (!$stmt->fetch()) {
        return ['ok' => false, 'error' => 'Color no válido'];
    }

    $stmt = $pdo->prepare(
        'SELECT 1 FROM producto_tallas WHERE producto_id = :id AND numero = :talla LIMIT 1'
    );
    $stmt->execute(['id' => $productoId, 'talla' => $tallaNumero]);
    if (!$stmt->fetch()) {
        return ['ok' => false, 'error' => 'Talla no válida'];
    }

    $disponibleInventario = inventario_variante_disponible($productoId, $colorCodigo, $tallaNumero);

    $stmt = $pdo->prepare(
        'SELECT disponible FROM producto_variantes
         WHERE producto_id = :id AND color_codigo = :color AND talla_numero = :talla LIMIT 1'
    );
    $stmt->execute([
        'id' => $productoId,
        'color' => $colorCodigo,
        'talla' => $tallaNumero,
    ]);
    $variante = $stmt->fetch();
    $disponibleLegacy = $variante ? (bool) $variante['disponible'] : true;

    if ($disponibleInventario === false || !$disponibleLegacy) {
        return ['ok' => false, 'error' => 'Ya no está disponible', 'agotado' => true];
    }

    if (db_tiene_inventario_variantes()) {
        if ($disponibleInventario === null) {
            inventario_sincronizar_producto($productoId);
        }
        inventario_marcar_agotado($productoId, $colorCodigo, $tallaNumero);
    }

    if ($variante) {
        $stmt = $pdo->prepare(
            'UPDATE producto_variantes SET disponible = 0
             WHERE producto_id = :id AND color_codigo = :color AND talla_numero = :talla'
        );
        $stmt->execute([
            'id' => $productoId,
            'color' => $colorCodigo,
            'talla' => $tallaNumero,
        ]);
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO producto_variantes (producto_id, color_codigo, talla_numero, disponible)
             VALUES (:id, :color, :talla, 0)'
        );
        $stmt->execute([
            'id' => $productoId,
            'color' => $colorCodigo,
            'talla' => $tallaNumero,
        ]);
    }

    return ['ok' => true];
}

function productos_contar(): int
{
    $pdo = db();
    return (int) $pdo->query('SELECT COUNT(*) FROM productos')->fetchColumn();
}
