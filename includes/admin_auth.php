<?php
declare(strict_types=1);

require_once __DIR__ . '/imagenes_producto.php';
require_once __DIR__ . '/series_tallas.php';

const ADMIN_SESION_RECORDAR_SEGUNDOS = 2592000;

function admin_sesion_usar_prolongada(): bool
{
    return isset($_COOKIE['leodri_admin_sesion_larga'])
        && $_COOKIE['leodri_admin_sesion_larga'] === '1';
}

function admin_cookie_path(): string
{
    $path = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/admin'));
    if ($path === '' || $path === '.' || $path === '/') {
        return '/';
    }

    return rtrim($path, '/') . '/';
}

function admin_cookie_opciones_base(): array
{
    return [
        'path' => admin_cookie_path(),
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ];
}

function admin_config(): array
{
    static $config = null;

    if ($config !== null) {
        return $config;
    }

    require_once __DIR__ . '/config.php';
    $config = config_cargar_opcional('admin', [
        'whatsapp_soporte' => '51935486809',
    ]);

    return $config;
}

function admin_whatsapp_soporte(): string
{
    return preg_replace('/\D/', '', (string) (admin_config()['whatsapp_soporte'] ?? ''));
}

function admin_enlace_recuperar_whatsapp(?string $usuario = null): string
{
    $numero = admin_whatsapp_soporte();
    if ($numero === '') {
        return '';
    }

    $mensaje = 'Hola, olvidé mi contraseña del panel admin LEODRI.';
    $usuario = trim((string) $usuario);
    if ($usuario !== '') {
        $mensaje .= ' Mi usuario es: ' . $usuario;
    }

    return 'https://wa.me/' . $numero . '?text=' . rawurlencode($mensaje);
}

function admin_sesion_iniciar(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $prolongada = admin_sesion_usar_prolongada();
    $lifetime = $prolongada ? ADMIN_SESION_RECORDAR_SEGUNDOS : 0;

    session_set_cookie_params(admin_cookie_opciones_base() + ['lifetime' => $lifetime]);

    if ($prolongada) {
        ini_set('session.gc_maxlifetime', (string) ADMIN_SESION_RECORDAR_SEGUNDOS);
    }

    session_start();
}

function admin_esta_logueado(): bool
{
    admin_sesion_iniciar();
    return !empty($_SESSION['admin_usuario_id']);
}

function admin_requerir_login(): void
{
    if (!admin_esta_logueado()) {
        header('Location: login.php');
        exit;
    }
}

function admin_csrf_token(): string
{
    admin_sesion_iniciar();
    if (empty($_SESSION['admin_csrf'])) {
        $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['admin_csrf'];
}

function admin_verificar_csrf(?string $token): bool
{
    admin_sesion_iniciar();
    return is_string($token)
        && !empty($_SESSION['admin_csrf'])
        && hash_equals($_SESSION['admin_csrf'], $token);
}

function admin_intentar_login(string $usuario, string $password): bool
{
    require_once __DIR__ . '/db.php';
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id, usuario, password_hash FROM admin_usuarios WHERE usuario = :usuario LIMIT 1');
    $stmt->execute(['usuario' => $usuario]);
    $fila = $stmt->fetch();

    if (!$fila || !password_verify($password, $fila['password_hash'])) {
        return false;
    }

    admin_sesion_iniciar();
    session_regenerate_id(true);
    $_SESSION['admin_usuario_id'] = (int) $fila['id'];
    $_SESSION['admin_usuario'] = $fila['usuario'];
    return true;
}

function admin_cerrar_sesion(): void
{
    admin_sesion_iniciar();
    $_SESSION = [];

    $opciones = admin_cookie_opciones_base() + ['expires' => time() - 3600];
    setcookie('leodri_admin_sesion_larga', '', $opciones);
    setcookie('leodri_admin_usuario', '', $opciones);

    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function admin_guardar_usuario_recordado(?string $usuario, bool $recordar): void
{
    $opciones = admin_cookie_opciones_base();

    if ($recordar && $usuario !== '') {
        setcookie(
            'leodri_admin_usuario',
            $usuario,
            $opciones + ['expires' => time() + ADMIN_SESION_RECORDAR_SEGUNDOS]
        );
        return;
    }

    setcookie('leodri_admin_usuario', '', $opciones + ['expires' => time() - 3600]);
}

function admin_aplicar_sesion_tras_login(bool $recordar, string $usuario): void
{
    $opciones = admin_cookie_opciones_base();

    if ($recordar) {
        setcookie(
            'leodri_admin_sesion_larga',
            '1',
            $opciones + ['expires' => time() + ADMIN_SESION_RECORDAR_SEGUNDOS]
        );
        admin_guardar_usuario_recordado($usuario, true);

        setcookie(
            session_name(),
            session_id(),
            $opciones + ['expires' => time() + ADMIN_SESION_RECORDAR_SEGUNDOS]
        );
        $_SESSION['admin_sesion_prolongada'] = true;

        return;
    }

    setcookie('leodri_admin_sesion_larga', '', $opciones + ['expires' => time() - 3600]);
    admin_guardar_usuario_recordado('', false);

    setcookie(session_name(), session_id(), $opciones + ['expires' => 0]);
    unset($_SESSION['admin_sesion_prolongada']);
}

function admin_usuario_recordado(): string
{
    return trim((string) ($_COOKIE['leodri_admin_usuario'] ?? ''));
}

function admin_generar_sku(string $codigoInventario, int $indiceColor, ?string $primeraTalla): array
{
    $base = $codigoInventario !== '' ? $codigoInventario : ('REF-C' . ($indiceColor + 1));
    $talla = $primeraTalla ?: '00';

    return [
        'sku_base' => $base . '-{talla}',
        'sku_sin_talla' => $base . '-' . $talla,
    ];
}

function admin_parsear_producto_post(array $post): array
{
    require_once __DIR__ . '/categorias_tienda.php';
    require_once __DIR__ . '/repositorio_series.php';

    $numerosStock = $post['stock_numero'] ?? [];
    $paresStock = $post['stock_pares'] ?? [];
    $disponibleStock = $post['stock_disponible'] ?? [];

    $tallas = [];
    $stockMapa = [];
    $variantesPrincipal = [];
    $numerosTalla = [];

    foreach ($numerosStock as $i => $numero) {
        $numero = trim((string) $numero);
        if ($numero === '') {
            continue;
        }
        $pares = max(0, (int) ($paresStock[$i] ?? 0));
        $disponible = ($disponibleStock[$i] ?? '1') === '1';

        $numerosTalla[] = $numero;
        $stockMapa[$numero] = $pares;
        $variantesPrincipal[$numero] = $disponible;
        $tallas[] = [
            'numero' => $numero,
            'disponible' => $disponible,
        ];
    }

    $primeraTalla = $numerosTalla[0] ?? null;

    $colores = [];
    $totalColores = max(
        count($post['colores']['codigo'] ?? []),
        count($post['colores']['etiqueta'] ?? []),
        count($post['colores']['imagen_actual'] ?? [])
    );

    for ($i = 0; $i < $totalColores; $i++) {
        $codigo = trim((string) ($post['colores']['codigo'][$i] ?? 'C' . ($i + 1)));
        if ($codigo === '') {
            $codigo = 'C' . ($i + 1);
        }
        $etiqueta = trim((string) ($post['colores']['etiqueta'][$i] ?? 'Color ' . ($i + 1)));
        if ($etiqueta === '') {
            $etiqueta = 'Color ' . ($i + 1);
        }

        $imagenes = [];
        foreach (imagenes_vistas() as $vista) {
            $imagenes[$vista] = trim((string) ($post['colores']['imagen_actual'][$i][$vista] ?? ''));
        }

        $sku = admin_generar_sku('', $i, $primeraTalla);
        $variantes = $i === 0 ? $variantesPrincipal : [];
        if ($variantes === []) {
            foreach ($numerosTalla as $numero) {
                $variantes[$numero] = true;
            }
        }

        $colores[] = [
            'codigo' => $codigo,
            'etiqueta' => $etiqueta,
            'imagen' => $imagenes['derecha'] ?? $imagenes['frente'] ?? '',
            'imagenes' => $imagenes,
            'alt' => '',
            'sku_base' => $sku['sku_base'],
            'sku_sin_talla' => $sku['sku_sin_talla'],
            'codigo_inventario' => '',
            'variantes' => $variantes,
        ];
    }

    $colorDefault = $colores[0]['codigo'] ?? '';
    $modelo = trim((string) ($post['modelo'] ?? $post['nombre'] ?? ''));
    $serieSlug = series_resolver_slug_producto((string) ($post['serie'] ?? 'SERIE_JUVENIL'));

    return [
        'marca' => trim((string) ($post['marca'] ?? '')),
        'nombre' => $modelo,
        'modelo' => $modelo,
        'tipo' => trim((string) ($post['tipo'] ?? '')),
        'publico' => trim((string) ($post['publico'] ?? 'unisex')),
        'descripcion' => trim((string) ($post['descripcion'] ?? '')),
        'categoria' => categoria_desde_request((string) ($post['categoria'] ?? CATEGORIA_HOME_DEFAULT)),
        'precio' => (float) str_replace(',', '.', (string) ($post['precio'] ?? '0')),
        'precio_anterior' => (float) str_replace(',', '.', (string) ($post['precio_anterior'] ?? '0')),
        'aplicar_descuento' => !empty($post['aplicar_descuento']),
        'serie' => $serieSlug,
        'serie_anterior' => trim((string) ($post['serie_anterior'] ?? '')),
        'color_default' => $colorDefault,
        'colores' => $colores,
        'tallas' => $tallas,
        'stock_mapa' => $stockMapa,
    ];
}

function admin_validar_producto(array $datos): array
{
    require_once __DIR__ . '/repositorio_series.php';

    $errores = [];

    if ($datos['marca'] === '') {
        $errores[] = 'La marca es obligatoria.';
    }
    if (trim((string) ($datos['modelo'] ?? $datos['nombre'] ?? '')) === '') {
        $errores[] = 'El modelo es obligatorio.';
    }
    if ($datos['precio'] <= 0) {
        $errores[] = 'Indica un precio válido.';
    }
    if (!publico_es_valido((string) ($datos['publico'] ?? ''))) {
        $errores[] = 'Selecciona un público objetivo válido.';
    }
    if (!empty($datos['aplicar_descuento'])) {
        $anterior = (float) ($datos['precio_anterior'] ?? 0);
        if ($anterior <= $datos['precio']) {
            $errores[] = 'El precio anterior debe ser mayor al precio de venta cuando aplicas descuento.';
        }
    }
    if (!series_es_slug_valido((string) ($datos['serie'] ?? ''))) {
        $errores[] = 'Selecciona una serie de tallas válida.';
    }
    if ($datos['colores'] === []) {
        $errores[] = 'Agrega al menos un color con fotos.';
    }
    if ($datos['tallas'] === []) {
        $errores[] = 'Agrega al menos una talla con stock.';
    }

    foreach ($datos['colores'] as $i => $color) {
        $derecha = $color['imagenes']['derecha'] ?? '';
        if ($derecha === '') {
            $errores[] = 'Se necesita la foto Derecha (miniatura del catálogo).';
            break;
        }
        if (trim((string) ($color['etiqueta'] ?? '')) === '') {
            $errores[] = 'Indica el nombre del color.';
            break;
        }
    }

    return $errores;
}
