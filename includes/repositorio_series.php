<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function db_migrar_series_carga(): void
{
    $pdo = db();

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS producto_series (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            slug VARCHAR(40) NOT NULL,
            codigo_corto VARCHAR(10) NOT NULL,
            nombre VARCHAR(120) NOT NULL,
            talla_min TINYINT UNSIGNED NOT NULL,
            talla_max TINYINT UNSIGNED NOT NULL,
            segmento VARCHAR(120) NOT NULL DEFAULT \'\',
            curva_docena JSON NOT NULL,
            tallas_especiales JSON NOT NULL,
            UNIQUE KEY uk_series_slug (slug),
            UNIQUE KEY uk_series_codigo (codigo_corto)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS preajustes_curvas (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            serie_id INT UNSIGNED NOT NULL,
            tipo ENUM(\'media_docena_central\', \'media_docena_chica\', \'media_docena_grande\') NOT NULL,
            nombre_curva VARCHAR(120) NOT NULL,
            cantidad_total TINYINT UNSIGNED NOT NULL DEFAULT 6,
            distribucion JSON NOT NULL,
            CONSTRAINT fk_curvas_serie
                FOREIGN KEY (serie_id) REFERENCES producto_series (id)
                ON DELETE CASCADE,
            UNIQUE KEY uk_curva_serie_tipo (serie_id, tipo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS producto_publicos (
            codigo VARCHAR(30) NOT NULL PRIMARY KEY,
            etiqueta VARCHAR(80) NOT NULL,
            genero ENUM(\'masculino\', \'femenino\', \'unisex\') NOT NULL DEFAULT \'unisex\',
            orden TINYINT UNSIGNED NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS producto_guias_tallas (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            producto_id INT UNSIGNED NOT NULL,
            serie_slug VARCHAR(40) NOT NULL,
            titulo VARCHAR(200) NOT NULL,
            filas JSON NOT NULL,
            imagen_instruccion VARCHAR(500) NOT NULL DEFAULT \'\',
            archivo_pdf VARCHAR(500) NOT NULL DEFAULT \'\',
            creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_guia_producto
                FOREIGN KEY (producto_id) REFERENCES productos (id)
                ON DELETE CASCADE,
            UNIQUE KEY uk_guia_producto_serie (producto_id, serie_slug)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $columnasProducto = [
        'modelo' => "VARCHAR(200) NOT NULL DEFAULT '' AFTER nombre",
        'tipo' => "VARCHAR(100) NOT NULL DEFAULT '' AFTER modelo",
        'publico' => "VARCHAR(30) NOT NULL DEFAULT 'unisex' AFTER categoria",
    ];
    foreach ($columnasProducto as $nombre => $definicion) {
        $stmt = $pdo->query('SHOW COLUMNS FROM productos LIKE ' . $pdo->quote($nombre));
        if (!$stmt->fetch()) {
            $pdo->exec('ALTER TABLE productos ADD COLUMN ' . $nombre . ' ' . $definicion);
        }
    }

    series_seed_si_vacio();
}

function series_datos_maestros(): array
{
    return [
        [
            'slug' => 'SERIE_CUNA',
            'codigo_corto' => 'CUN',
            'nombre' => 'Cuna (No Caminante)',
            'talla_min' => 15,
            'talla_max' => 18,
            'segmento' => 'Bebés de 0 a 12 meses',
            'curva_docena' => ['15' => 3, '16' => 3, '17' => 3, '18' => 3],
            'tallas_especiales' => [],
            'medias' => [
                'media_docena_central' => ['15' => 1, '16' => 2, '17' => 2, '18' => 1],
                'media_docena_chica' => ['15' => 2, '16' => 2, '17' => 2],
                'media_docena_grande' => ['16' => 1, '17' => 2, '18' => 3],
            ],
        ],
        [
            'slug' => 'SERIE_BABY',
            'codigo_corto' => 'BAB',
            'nombre' => 'Baby / Caminante',
            'talla_min' => 19,
            'talla_max' => 22,
            'segmento' => 'Niños de 1 a 3 años',
            'curva_docena' => ['19' => 2, '20' => 3, '21' => 4, '22' => 3],
            'tallas_especiales' => [],
            'medias' => [
                'media_docena_central' => ['20' => 2, '21' => 2, '22' => 2],
                'media_docena_chica' => ['19' => 2, '20' => 2, '21' => 2],
                'media_docena_grande' => ['20' => 1, '21' => 2, '22' => 3],
            ],
        ],
        [
            'slug' => 'SERIE_INFANTIL',
            'codigo_corto' => 'INF',
            'nombre' => 'Infantil',
            'talla_min' => 23,
            'talla_max' => 26,
            'segmento' => 'Niños de 3 a 5 años',
            'curva_docena' => ['23' => 2, '24' => 3, '25' => 4, '26' => 3],
            'tallas_especiales' => [],
            'medias' => [
                'media_docena_central' => ['24' => 2, '25' => 2, '26' => 2],
                'media_docena_chica' => ['23' => 2, '24' => 2, '25' => 2],
                'media_docena_grande' => ['24' => 1, '25' => 2, '26' => 3],
            ],
        ],
        [
            'slug' => 'SERIE_JUNIOR',
            'codigo_corto' => 'JUN',
            'nombre' => 'Junior / Niños Grandes',
            'talla_min' => 27,
            'talla_max' => 32,
            'segmento' => 'Escolar Inicial (6 a 9 años)',
            'curva_docena' => ['27' => 1, '28' => 2, '29' => 3, '30' => 3, '31' => 2, '32' => 1],
            'tallas_especiales' => [],
            'medias' => [
                'media_docena_central' => ['28' => 2, '29' => 2, '30' => 2],
                'media_docena_chica' => ['27' => 1, '28' => 2, '29' => 2, '30' => 1],
                'media_docena_grande' => ['29' => 1, '30' => 2, '31' => 2, '32' => 1],
            ],
        ],
        [
            'slug' => 'SERIE_JUVENIL',
            'codigo_corto' => 'JUV',
            'nombre' => 'Juvenil / Escolar',
            'talla_min' => 33,
            'talla_max' => 37,
            'segmento' => 'Adolescentes (10 a 14 años)',
            'curva_docena' => ['33' => 2, '34' => 2, '35' => 3, '36' => 3, '37' => 2],
            'tallas_especiales' => [],
            'medias' => [
                'media_docena_central' => ['34' => 2, '35' => 2, '36' => 2],
                'media_docena_chica' => ['33' => 2, '34' => 2, '35' => 2],
                'media_docena_grande' => ['35' => 1, '36' => 2, '37' => 3],
            ],
        ],
        [
            'slug' => 'SERIE_DAMAS',
            'codigo_corto' => 'DAM',
            'nombre' => 'Damas (Adulto Mujer)',
            'talla_min' => 34,
            'talla_max' => 40,
            'segmento' => 'Mujeres adultas',
            'curva_docena' => ['34' => 1, '35' => 2, '36' => 3, '37' => 3, '38' => 2, '39' => 1],
            'tallas_especiales' => [40],
            'medias' => [
                'media_docena_central' => ['35' => 1, '36' => 2, '37' => 2, '38' => 1],
                'media_docena_chica' => ['34' => 1, '35' => 2, '36' => 2, '37' => 1],
                'media_docena_grande' => ['36' => 1, '37' => 2, '38' => 2, '39' => 1],
            ],
        ],
        [
            'slug' => 'SERIE_CABALLEROS',
            'codigo_corto' => 'CAB',
            'nombre' => 'Caballeros (Adulto Varón)',
            'talla_min' => 38,
            'talla_max' => 45,
            'segmento' => 'Varones adultos',
            'curva_docena' => ['38' => 1, '39' => 2, '40' => 3, '41' => 3, '42' => 2, '43' => 1],
            'tallas_especiales' => [44, 45],
            'medias' => [
                'media_docena_central' => ['39' => 1, '40' => 2, '41' => 2, '42' => 1],
                'media_docena_chica' => ['38' => 1, '39' => 2, '40' => 2, '41' => 1],
                'media_docena_grande' => ['40' => 1, '41' => 2, '42' => 2, '43' => 1],
            ],
        ],
    ];
}

function series_alias_codigos(): array
{
    return [
        'SH' => 'CAB',
    ];
}

function series_seed_si_vacio(): void
{
    $pdo = db();
    $count = (int) $pdo->query('SELECT COUNT(*) FROM producto_series')->fetchColumn();
    if ($count > 0) {
        return;
    }

    $insertSerie = $pdo->prepare(
        'INSERT INTO producto_series (slug, codigo_corto, nombre, talla_min, talla_max, segmento, curva_docena, tallas_especiales)
         VALUES (:slug, :codigo_corto, :nombre, :talla_min, :talla_max, :segmento, :curva_docena, :tallas_especiales)'
    );
    $insertCurva = $pdo->prepare(
        'INSERT INTO preajustes_curvas (serie_id, tipo, nombre_curva, cantidad_total, distribucion)
         VALUES (:serie_id, :tipo, :nombre_curva, :cantidad_total, :distribucion)'
    );

    $nombresCurva = [
        'media_docena_central' => 'Curva Central',
        'media_docena_chica' => 'Curva Chica',
        'media_docena_grande' => 'Curva Grande',
    ];

    foreach (series_datos_maestros() as $fila) {
        $insertSerie->execute([
            'slug' => $fila['slug'],
            'codigo_corto' => $fila['codigo_corto'],
            'nombre' => $fila['nombre'],
            'talla_min' => $fila['talla_min'],
            'talla_max' => $fila['talla_max'],
            'segmento' => $fila['segmento'],
            'curva_docena' => json_encode($fila['curva_docena'], JSON_UNESCAPED_UNICODE),
            'tallas_especiales' => json_encode($fila['tallas_especiales'], JSON_UNESCAPED_UNICODE),
        ]);
        $serieId = (int) $pdo->lastInsertId();

        foreach ($fila['medias'] as $tipo => $distribucion) {
            $total = array_sum($distribucion);
            $insertCurva->execute([
                'serie_id' => $serieId,
                'tipo' => $tipo,
                'nombre_curva' => $fila['nombre'] . ' — ' . $nombresCurva[$tipo],
                'cantidad_total' => $total,
                'distribucion' => json_encode($distribucion, JSON_UNESCAPED_UNICODE),
            ]);
        }
    }

    $publicos = [
        ['bebe', 'Bebé', 'unisex', 1],
        ['beba', 'Beba', 'femenino', 2],
        ['nino', 'Niño', 'masculino', 3],
        ['nina', 'Niña', 'femenino', 4],
        ['joven', 'Joven', 'unisex', 5],
        ['senorita', 'Señorita', 'femenino', 6],
        ['dama', 'Dama', 'femenino', 7],
        ['caballero', 'Caballero', 'masculino', 8],
        ['unisex', 'Unisex', 'unisex', 9],
    ];
    $insertPublico = $pdo->prepare(
        'INSERT INTO producto_publicos (codigo, etiqueta, genero, orden) VALUES (?, ?, ?, ?)'
    );
    foreach ($publicos as $p) {
        $insertPublico->execute($p);
    }
}

function series_listar(): array
{
    db_migrar_series_carga();
    $pdo = db();
    $filas = $pdo->query('SELECT * FROM producto_series ORDER BY talla_min ASC')->fetchAll();

    foreach ($filas as &$fila) {
        $fila['curva_docena'] = json_decode((string) $fila['curva_docena'], true) ?: [];
        $fila['tallas_especiales'] = json_decode((string) $fila['tallas_especiales'], true) ?: [];
    }

    return $filas;
}

function series_normalizar_codigo_corto(string $codigo): string
{
    $codigo = strtoupper(trim($codigo));
    $alias = series_alias_codigos();

    return $alias[$codigo] ?? $codigo;
}

function series_obtener_por_codigo_corto(string $codigo): ?array
{
    db_migrar_series_carga();
    $codigo = series_normalizar_codigo_corto($codigo);
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM producto_series WHERE codigo_corto = :codigo LIMIT 1');
    $stmt->execute(['codigo' => $codigo]);
    $fila = $stmt->fetch();
    if (!$fila) {
        return null;
    }

    $fila['curva_docena'] = json_decode((string) $fila['curva_docena'], true) ?: [];
    $fila['tallas_especiales'] = json_decode((string) $fila['tallas_especiales'], true) ?: [];

    return $fila;
}

function series_obtener_por_slug(string $slug): ?array
{
    db_migrar_series_carga();
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM producto_series WHERE slug = :slug LIMIT 1');
    $stmt->execute(['slug' => $slug]);
    $fila = $stmt->fetch();
    if (!$fila) {
        return null;
    }

    $fila['curva_docena'] = json_decode((string) $fila['curva_docena'], true) ?: [];
    $fila['tallas_especiales'] = json_decode((string) $fila['tallas_especiales'], true) ?: [];

    return $fila;
}

function series_distribucion(string $codigoSerie, string $tipoLote, string $tipoMedia = 'central'): array
{
    $serie = series_obtener_por_codigo_corto($codigoSerie);
    if ($serie === null) {
        return [];
    }

    if ($tipoLote === 'docena') {
        return $serie['curva_docena'];
    }

    $mapaTipos = [
        'central' => 'media_docena_central',
        'chica' => 'media_docena_chica',
        'grande' => 'media_docena_grande',
    ];
    $tipoDb = $mapaTipos[$tipoMedia] ?? 'media_docena_central';

    $pdo = db();
    $stmt = $pdo->prepare(
        'SELECT distribucion FROM preajustes_curvas WHERE serie_id = :id AND tipo = :tipo LIMIT 1'
    );
    $stmt->execute(['id' => (int) $serie['id'], 'tipo' => $tipoDb]);
    $json = $stmt->fetchColumn();
    if ($json === false) {
        return [];
    }

    $dist = json_decode((string) $json, true);

    return is_array($dist) ? $dist : [];
}

function series_validar_talla_en_serie(array $serie, string $numeroTalla, bool $esEspecialManual = false): bool
{
    $numero = (int) $numeroTalla;
    if ($numero < (int) $serie['talla_min'] || $numero > (int) $serie['talla_max']) {
        if ($esEspecialManual && in_array($numero, $serie['tallas_especiales'], true)) {
            return true;
        }

        return false;
    }

    return true;
}

function publicos_listar(): array
{
    db_migrar_series_carga();
    $pdo = db();

    return $pdo->query('SELECT * FROM producto_publicos ORDER BY orden ASC, etiqueta ASC')->fetchAll();
}

function publico_es_valido(string $codigo): bool
{
    db_migrar_series_carga();
    $pdo = db();
    $stmt = $pdo->prepare('SELECT 1 FROM producto_publicos WHERE codigo = :codigo LIMIT 1');
    $stmt->execute(['codigo' => $codigo]);

    return (bool) $stmt->fetchColumn();
}

function series_resolver_slug_producto(string $serieGuardada): string
{
    $serieGuardada = trim($serieGuardada);
    if ($serieGuardada === '') {
        return 'SERIE_JUVENIL';
    }

    $porCodigo = series_obtener_por_codigo_corto($serieGuardada);
    if ($porCodigo !== null) {
        return (string) $porCodigo['slug'];
    }

    $porSlug = series_obtener_por_slug($serieGuardada);
    if ($porSlug !== null) {
        return (string) $porSlug['slug'];
    }

    return $serieGuardada;
}

function series_es_slug_valido(string $slug): bool
{
    return series_obtener_por_slug(series_resolver_slug_producto($slug)) !== null;
}

function series_etiqueta_producto(string $serieGuardada): string
{
    $serie = series_obtener_por_slug(series_resolver_slug_producto($serieGuardada));
    if ($serie === null) {
        return $serieGuardada;
    }

    return sprintf(
        '%s (%s, EU %d–%d)',
        (string) $serie['nombre'],
        (string) $serie['codigo_corto'],
        (int) $serie['talla_min'],
        (int) $serie['talla_max']
    );
}
