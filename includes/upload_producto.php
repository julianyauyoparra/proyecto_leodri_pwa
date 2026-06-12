<?php
declare(strict_types=1);

require_once __DIR__ . '/imagenes_producto.php';

function upload_procesar_lote_color(int $indiceColor, array $files, int $productoId, array $imagenes): array
{
    $nombres = $files['colores']['name'][$indiceColor]['lote'] ?? null;
    if (!is_array($nombres)) {
        return $imagenes;
    }

    $vistasUsadas = [];
    foreach ($nombres as $j => $nombre) {
        $tmp = $files['colores']['tmp_name'][$indiceColor]['lote'][$j] ?? '';
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            continue;
        }

        $vista = imagenes_vista_desde_nombre_archivo((string) $nombre);
        if ($vista === null) {
            throw new RuntimeException(
                'No se reconoce la vista en «' . $nombre . '». '
                . 'El nombre del archivo debe incluir: derecha, izquierda, frente, posterior, arriba o abajo.'
            );
        }
        if (isset($vistasUsadas[$vista])) {
            throw new RuntimeException(
                'Hay más de un archivo para la vista «' . $vista . '»: «'
                . $vistasUsadas[$vista] . '» y «' . $nombre . '».'
            );
        }
        $vistasUsadas[$vista] = (string) $nombre;

        $subida = imagenes_subir_archivo([
            'tmp_name' => $tmp,
            'error' => $files['colores']['error'][$indiceColor]['lote'][$j] ?? UPLOAD_ERR_OK,
        ], $productoId, $indiceColor, $vista);
        if ($subida !== null) {
            $imagenes[$vista] = $subida;
        }
    }

    return $imagenes;
}

function upload_procesar_colores(?int $productoId, array $post, array $files, array $coloresParseados): array
{
    if ($productoId === null || $productoId <= 0) {
        return $coloresParseados;
    }

    $colores = [];

    foreach ($coloresParseados as $i => $color) {
        $imagenes = [];
        foreach (imagenes_vistas() as $vista) {
            $imagenes[$vista] = trim((string) ($post['colores']['imagen_actual'][$i][$vista] ?? $color['imagenes'][$vista] ?? ''));
        }

        $imagenes = upload_procesar_lote_color($i, $files, $productoId, $imagenes);

        $color['imagenes'] = $imagenes;
        $color['imagen'] = imagen_thumbnail_desde_vistas($imagenes, $color['imagen'] ?? '');
        $color['alt'] = trim((string) ($post['marca'] ?? '')) . ' ' . trim((string) ($post['nombre'] ?? ''));
        $colores[] = $color;
    }

    return $colores;
}

function upload_requiere_thumbnail(array $colores): bool
{
    foreach ($colores as $color) {
        if (($color['imagenes']['derecha'] ?? '') !== '') {
            return true;
        }
    }
    return false;
}
