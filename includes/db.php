<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function db_config(): array
{
    static $config = null;

    if ($config !== null) {
        return $config;
    }

    $config = config_cargar('database');

    return $config;
}

function db_resolver_host_puerto(array $cfg): array
{
    $host = trim((string) ($cfg['host'] ?? 'localhost'));
    $puerto = isset($cfg['port']) ? (int) $cfg['port'] : 3306;

    if (preg_match('/;port=(\d+)/i', $host, $coincidencias) === 1) {
        $host = trim((string) preg_replace('/;port=\d+/i', '', $host));
        $puerto = (int) $coincidencias[1];
    }

    return [$host, $puerto];
}

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $cfg = db_config();
    [$host, $puerto] = db_resolver_host_puerto($cfg);
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $host,
        $puerto,
        $cfg['dbname'],
        $cfg['charset'] ?? 'utf8mb4'
    );

    $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

function db_sin_base(): PDO
{
    $cfg = db_config();
    [$host, $puerto] = db_resolver_host_puerto($cfg);
    $dsn = sprintf('mysql:host=%s;port=%d;charset=%s', $host, $puerto, $cfg['charset'] ?? 'utf8mb4');

    return new PDO($dsn, $cfg['user'], $cfg['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}
