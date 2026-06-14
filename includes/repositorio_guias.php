<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/repositorio_series.php';

const GUIA_PDF_MAX_BYTES = 10_485_760; // 10 MB

function db_migrar_guia_pdf(): void
{
    static $migrado = false;
    if ($migrado) {
        return;
    }

    db_migrar_series_carga();
    $pdo = db();
    $stmt = $pdo->query("SHOW COLUMNS FROM producto_guias_tallas LIKE 'archivo_pdf'");
    if (!$stmt->fetch()) {
        $pdo->exec(
            'ALTER TABLE producto_guias_tallas
             ADD COLUMN archivo_pdf VARCHAR(500) NOT NULL DEFAULT \'\' AFTER imagen_instruccion'
        );
    }

    $migrado = true;
}

function guia_guardar(int $productoId, string $serieSlug, string $titulo, string $archivoPdf): void
{
    db_migrar_guia_pdf();
    $pdo = db();
    $stmt = $pdo->prepare(
        'INSERT INTO producto_guias_tallas (producto_id, serie_slug, titulo, filas, imagen_instruccion, archivo_pdf)
         VALUES (:producto_id, :serie_slug, :titulo, :filas, :imagen_instruccion, :archivo_pdf)
         ON DUPLICATE KEY UPDATE
            titulo = VALUES(titulo),
            filas = VALUES(filas),
            imagen_instruccion = VALUES(imagen_instruccion),
            archivo_pdf = VALUES(archivo_pdf)'
    );
    $stmt->execute([
        'producto_id' => $productoId,
        'serie_slug' => $serieSlug,
        'titulo' => $titulo,
        'filas' => '[]',
        'imagen_instruccion' => '',
        'archivo_pdf' => $archivoPdf,
    ]);
}

function guia_obtener(int $productoId, string $serieSlug): ?array
{
    db_migrar_guia_pdf();
    $pdo = db();
    $stmt = $pdo->prepare(
        'SELECT * FROM producto_guias_tallas WHERE producto_id = :id AND serie_slug = :slug LIMIT 1'
    );
    $stmt->execute(['id' => $productoId, 'slug' => $serieSlug]);
    $fila = $stmt->fetch();
    if (!$fila) {
        return null;
    }

    $fila['filas'] = json_decode((string) $fila['filas'], true) ?: [];

    return $fila;
}

function guia_validar_pdf_upload(array $file): array
{
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => 'Debe subir el PDF de la guía de tallas.'];
    }
    if ($error !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'No se pudo subir el PDF de la guía de tallas.'];
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return ['ok' => false, 'error' => 'El archivo PDF de la guía no es válido.'];
    }

    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0 || $size > GUIA_PDF_MAX_BYTES) {
        return ['ok' => false, 'error' => 'El PDF de la guía debe pesar entre 1 byte y 10 MB.'];
    }

    $nombre = (string) ($file['name'] ?? '');
    $extension = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
    if ($extension !== 'pdf') {
        return ['ok' => false, 'error' => 'La guía de tallas debe ser un archivo PDF (.pdf).'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? finfo_file($finfo, $tmp) : '';
    if ($finfo) {
        finfo_close($finfo);
    }
    if ($mime !== '' && $mime !== 'application/pdf' && $mime !== 'application/x-pdf') {
        return ['ok' => false, 'error' => 'La guía de tallas debe ser un PDF válido.'];
    }

    return ['ok' => true, 'tmp' => $tmp];
}

function guia_guardar_pdf_producto(int $productoId, array $file): array
{
    $validacion = guia_validar_pdf_upload($file);
    if (!$validacion['ok']) {
        return $validacion;
    }

    $dirFs = dirname(__DIR__) . '/assets/productos/' . $productoId;
    if (!is_dir($dirFs) && !mkdir($dirFs, 0755, true) && !is_dir($dirFs)) {
        return ['ok' => false, 'error' => 'No se pudo crear la carpeta del producto para la guía PDF.'];
    }

    $destinoFs = $dirFs . '/guia-tallas.pdf';
    if (!move_uploaded_file((string) $validacion['tmp'], $destinoFs)) {
        return ['ok' => false, 'error' => 'No se pudo guardar el PDF de la guía de tallas.'];
    }

    return [
        'ok' => true,
        'ruta' => 'assets/productos/' . $productoId . '/guia-tallas.pdf',
    ];
}

function guia_render_html(array $guia): string
{
    $filas = $guia['filas'] ?? [];
    if ($filas === []) {
        return '';
    }

    $html = '<div class="ficha-guia__tabla-wrap"><table class="ficha-guia__tabla"><thead><tr>'
        . '<th>Talla</th><th>Equivalencia</th><th>Cm</th></tr></thead><tbody>';
    foreach ($filas as $fila) {
        $html .= '<tr><td>' . h((string) ($fila['talla'] ?? '')) . '</td>'
            . '<td>' . h((string) ($fila['equivalencia'] ?? '')) . '</td>'
            . '<td>' . h((string) ($fila['cm'] ?? '')) . '</td></tr>';
    }
    $html .= '</tbody></table></div>';

    $imagen = trim((string) ($guia['imagen_instruccion'] ?? ''));
    if ($imagen !== '') {
        $html .= '<p class="ficha-guia__intro">¿Cómo medir su talla?</p>'
            . '<p><img src="' . h($imagen) . '" alt="Cómo medir su talla" class="ficha-guia__instruccion-img" loading="lazy"></p>';
    }

    return $html;
}
