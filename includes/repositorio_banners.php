<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

const BANNERS_MAXIMO = 6;
const BANNERS_DIR_PUBLICO = 'assets/banners/';

function db_migrar_hero_banners(): void
{
    $pdo = db();
    $stmt = $pdo->query("SHOW TABLES LIKE 'hero_banners'");
    if (!$stmt->fetch()) {
        $pdo->exec(
            'CREATE TABLE hero_banners (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                imagen VARCHAR(500) NOT NULL,
                ancho INT UNSIGNED NOT NULL DEFAULT 0,
                alto INT UNSIGNED NOT NULL DEFAULT 0,
                alt VARCHAR(255) NOT NULL DEFAULT \'\',
                url_destino VARCHAR(500) NOT NULL DEFAULT \'catalogo.php\',
                creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_hero_banners_creado (creado_en)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    banners_seed_por_defecto();
}

function banners_dir_absoluto(): string
{
    $dir = dirname(__DIR__) . '/' . BANNERS_DIR_PUBLICO;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    return $dir;
}

function banners_seed_por_defecto(): void
{
    $pdo = db();
    $total = (int) $pdo->query('SELECT COUNT(*) FROM hero_banners')->fetchColumn();
    if ($total > 0) {
        return;
    }

    $defectos = [
        ['assets/banners/hero-1.png', '2x1 en zapatillas seleccionadas — promoción LEODRI'],
        ['assets/banners/hero-2.png', '50% off en el segundo par de nueva colección'],
        ['assets/banners/hero-3.png', 'Zapatillas desde S/ 99'],
    ];

    $stmt = $pdo->prepare(
        'INSERT INTO hero_banners (imagen, ancho, alto, alt, url_destino, creado_en)
         VALUES (:imagen, :ancho, :alto, :alt, :url, :creado)'
    );

    $base = dirname(__DIR__);
    $offset = 0;
    foreach ($defectos as $defecto) {
        $rutaAbs = $base . '/' . $defecto[0];
        if (!is_readable($rutaAbs)) {
            continue;
        }
        $dimensiones = @getimagesize($rutaAbs);
        $ancho = is_array($dimensiones) ? (int) $dimensiones[0] : 0;
        $alto = is_array($dimensiones) ? (int) $dimensiones[1] : 0;
        $creado = (new DateTimeImmutable('now'))->modify('-' . (count($defectos) - $offset) . ' minutes')->format('Y-m-d H:i:s');
        $stmt->execute([
            'imagen' => $defecto[0],
            'ancho' => $ancho,
            'alto' => $alto,
            'alt' => $defecto[1],
            'url' => 'catalogo.php',
            'creado' => $creado,
        ]);
        $offset++;
    }
}

/** @return list<array{id:int, imagen:string, ancho:int, alto:int, alt:string, url_destino:string, creado_en:string}> */
function banners_listar(): array
{
    db_migrar_hero_banners();
    $pdo = db();
    $stmt = $pdo->query(
        'SELECT id, imagen, ancho, alto, alt, url_destino, creado_en
         FROM hero_banners
         ORDER BY creado_en ASC, id ASC'
    );

    return $stmt->fetchAll() ?: [];
}

/** @return list<array<string, mixed>> */
function banners_listar_fallback(): array
{
    return [
        [
            'id' => 0,
            'imagen' => 'assets/banners/hero-1.png',
            'ancho' => 1024,
            'alto' => 320,
            'alt' => '2x1 en zapatillas seleccionadas — promoción LEODRI',
            'url_destino' => 'catalogo.php',
        ],
        [
            'id' => 0,
            'imagen' => 'assets/banners/hero-2.png',
            'ancho' => 1024,
            'alto' => 320,
            'alt' => '50% off en el segundo par de nueva colección',
            'url_destino' => 'catalogo.php',
        ],
        [
            'id' => 0,
            'imagen' => 'assets/banners/hero-3.png',
            'ancho' => 1024,
            'alto' => 320,
            'alt' => 'Zapatillas desde S/ 99',
            'url_destino' => 'catalogo.php',
        ],
    ];
}

/** @return list<array<string, mixed>> */
function banners_listar_para_tienda(): array
{
    try {
        $lista = banners_listar();
        if ($lista !== []) {
            return $lista;
        }
    } catch (Throwable $e) {
        /* fallback estático */
    }

    return banners_listar_fallback();
}

function banners_validar_archivo(array $archivo): void
{
    if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        throw new RuntimeException('Elige una imagen (JPG, PNG o WEBP).');
    }
    if (($archivo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Error al subir la imagen.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($archivo['tmp_name']);
    $permitidos = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($mime, $permitidos, true)) {
        throw new RuntimeException('Formato no permitido. Usa JPG, PNG o WEBP.');
    }

    $dimensiones = @getimagesize($archivo['tmp_name']);
    if (!is_array($dimensiones)) {
        throw new RuntimeException('No se pudo leer la imagen.');
    }

    if ((int) $dimensiones[0] < 1024) {
        throw new RuntimeException('La imagen debe tener al menos 1024 px de ancho. Para pantallas grandes, usa 1920 px o más.');
    }
}

/**
 * @return array{ancho:int, alto:int, extension:string}
 */
function banners_leer_archivo(array $archivo): array
{
    banners_validar_archivo($archivo);

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($archivo['tmp_name']);
    $mapa = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    $dimensiones = getimagesize($archivo['tmp_name']);

    return [
        'ancho' => (int) $dimensiones[0],
        'alto' => (int) $dimensiones[1],
        'extension' => $mapa[$mime],
    ];
}

function banners_guardar_archivo(array $archivo, int $id, string $extension): string
{
    $destino = banners_dir_absoluto() . 'banner-' . $id . '.' . $extension;
    if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
        throw new RuntimeException('No se pudo guardar la imagen en el servidor.');
    }

    return BANNERS_DIR_PUBLICO . 'banner-' . $id . '.' . $extension;
}

function banners_eliminar_archivo(?string $rutaPublica): void
{
    if ($rutaPublica === null || $rutaPublica === '') {
        return;
    }
    if (!str_starts_with($rutaPublica, BANNERS_DIR_PUBLICO)) {
        return;
    }

    $rutaAbs = dirname(__DIR__) . '/' . $rutaPublica;
    if (is_file($rutaAbs)) {
        @unlink($rutaAbs);
    }
}

function banners_eliminar(int $id): void
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT imagen FROM hero_banners WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $fila = $stmt->fetch();
    if (!$fila) {
        return;
    }

    banners_eliminar_archivo($fila['imagen']);
    $pdo->prepare('DELETE FROM hero_banners WHERE id = :id')->execute(['id' => $id]);
}

function banners_insertar(array $archivo, string $alt = '', string $urlDestino = 'catalogo.php'): int
{
    $meta = banners_leer_archivo($archivo);
    $pdo = db();
    $pdo->prepare(
        'INSERT INTO hero_banners (imagen, ancho, alto, alt, url_destino) VALUES (\'\', :ancho, :alto, :alt, :url)'
    )->execute([
        'ancho' => $meta['ancho'],
        'alto' => $meta['alto'],
        'alt' => $alt !== '' ? $alt : 'Promoción LEODRI',
        'url' => $urlDestino !== '' ? $urlDestino : 'catalogo.php',
    ]);

    $id = (int) $pdo->lastInsertId();
    $rutaPublica = banners_guardar_archivo($archivo, $id, $meta['extension']);
    $pdo->prepare('UPDATE hero_banners SET imagen = :imagen WHERE id = :id')->execute([
        'imagen' => $rutaPublica,
        'id' => $id,
    ]);

    return $id;
}

function banners_reemplazar_archivo(int $id, array $archivo, string $alt = '', string $urlDestino = 'catalogo.php'): void
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT imagen FROM hero_banners WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $fila = $stmt->fetch();
    if (!$fila) {
        throw new RuntimeException('Banner no encontrado.');
    }

    $meta = banners_leer_archivo($archivo);
    banners_eliminar_archivo($fila['imagen']);

    $rutaPublica = banners_guardar_archivo($archivo, $id, $meta['extension']);
    $pdo->prepare(
        'UPDATE hero_banners
         SET imagen = :imagen, ancho = :ancho, alto = :alto, alt = :alt, url_destino = :url
         WHERE id = :id'
    )->execute([
        'imagen' => $rutaPublica,
        'ancho' => $meta['ancho'],
        'alto' => $meta['alto'],
        'alt' => $alt !== '' ? $alt : 'Promoción LEODRI',
        'url' => $urlDestino !== '' ? $urlDestino : 'catalogo.php',
        'id' => $id,
    ]);
}

/**
 * @return array{accion:string, total:int}
 */
function banners_subir(array $archivo, bool $mantenerTodas, string $alt = '', string $urlDestino = 'catalogo.php'): array
{
    db_migrar_hero_banners();
    $lista = banners_listar();
    $total = count($lista);

    if (!$mantenerTodas) {
        if ($total === 0) {
            banners_insertar($archivo, $alt, $urlDestino);
            return ['accion' => 'agregado', 'total' => 1];
        }

        $masAntiguo = $lista[0];
        banners_reemplazar_archivo((int) $masAntiguo['id'], $archivo, $alt, $urlDestino);

        return ['accion' => 'reemplazado', 'total' => $total];
    }

    if ($total >= BANNERS_MAXIMO) {
        banners_eliminar((int) $lista[0]['id']);
        $total--;
    }

    banners_insertar($archivo, $alt, $urlDestino);

    return ['accion' => 'agregado', 'total' => $total + 1];
}

function banners_contar(): int
{
    try {
        db_migrar_hero_banners();

        return (int) db()->query('SELECT COUNT(*) FROM hero_banners')->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}
