<?php
declare(strict_types=1);

function imagenes_vistas(): array
{
    return ['frente', 'derecha', 'izquierda', 'posterior', 'arriba', 'abajo'];
}

function imagenes_vistas_etiquetas(): array
{
    return [
        'frente' => 'Frente',
        'derecha' => 'Derecha',
        'izquierda' => 'Izquierda',
        'posterior' => 'Posterior',
        'arriba' => 'Arriba',
        'abajo' => 'Abajo',
    ];
}

function imagenes_vista_desde_nombre_archivo(string $nombreArchivo): ?string
{
    $base = strtolower(pathinfo($nombreArchivo, PATHINFO_FILENAME));
    foreach (imagenes_vistas() as $vista) {
        if (str_contains($base, $vista)) {
            return $vista;
        }
    }

    return null;
}

function imagenes_normalizar_color(array $color): array
{
    $imagenes = $color['imagenes'] ?? [];
    if (is_string($imagenes)) {
        $imagenes = json_decode($imagenes, true) ?: [];
    }
    if (!is_array($imagenes)) {
        $imagenes = [];
    }

    if (empty($imagenes['frente']) && !empty($color['imagen'])) {
        $imagenes['frente'] = $color['imagen'];
    }

    foreach (imagenes_vistas() as $vista) {
        if (!isset($imagenes[$vista])) {
            $imagenes[$vista] = '';
        }
    }

    $color['imagenes'] = $imagenes;
    $color['imagen'] = imagen_thumbnail_desde_vistas($imagenes, $color['imagen'] ?? '');

    return $color;
}

function imagen_thumbnail_desde_vistas(array $imagenes, string $fallback = ''): string
{
    if (!empty($imagenes['derecha'])) {
        return $imagenes['derecha'];
    }
    if (!empty($imagenes['frente'])) {
        return $imagenes['frente'];
    }

    return $fallback;
}

function imagen_thumbnail(array $color): string
{
    $color = imagenes_normalizar_color($color);

    return $color['imagen'];
}

function db_migrar_imagenes(): void
{
    $pdo = db();
    $stmt = $pdo->query("SHOW COLUMNS FROM producto_colores LIKE 'imagenes'");
    if ($stmt->fetch()) {
        return;
    }
    $pdo->exec('ALTER TABLE producto_colores ADD COLUMN imagenes JSON NULL AFTER imagen');
}

function imagenes_ruta_destino(int $productoId, int $indiceColor, string $vista, string $extension): string
{
    $slug = 'color-' . ($indiceColor + 1);
    $dir = dirname(__DIR__) . '/assets/productos/' . $productoId . '/' . $slug;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    return $dir . '/' . $vista . '.' . $extension;
}

function imagenes_ruta_publica(int $productoId, int $indiceColor, string $vista, string $extension): string
{
    $slug = 'color-' . ($indiceColor + 1);
    return 'assets/productos/' . $productoId . '/' . $slug . '/' . $vista . '.' . $extension;
}

function imagenes_subir_archivo(array $archivo, int $productoId, int $indiceColor, string $vista): ?string
{
    if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($archivo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Error al subir la imagen (' . $vista . ').');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($archivo['tmp_name']);
    $mapa = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($mapa[$mime])) {
        throw new RuntimeException('Formato no permitido en ' . $vista . '. Usa JPG, PNG o WEBP.');
    }

    $ext = $mapa[$mime];
    $destino = imagenes_ruta_destino($productoId, $indiceColor, $vista, $ext);

    if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
        throw new RuntimeException('No se pudo guardar la imagen (' . $vista . ').');
    }

    return imagenes_ruta_publica($productoId, $indiceColor, $vista, $ext);
}
