<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function api_agente_config(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $config = config_cargar_opcional('api', ['agent_api_key' => '']);

    return $config;
}

function api_agente_obtener_clave_request(array $input = []): string
{
    $header = trim((string) ($_SERVER['HTTP_X_API_KEY'] ?? ''));
    if ($header !== '') {
        return $header;
    }

    return trim((string) ($input['api_key'] ?? ''));
}

function api_agente_verificar_clave(?string $clave): bool
{
    $esperada = (string) (api_agente_config()['agent_api_key'] ?? '');
    $clave = trim((string) $clave);

    return $esperada !== '' && $clave !== '' && hash_equals($esperada, $clave);
}
