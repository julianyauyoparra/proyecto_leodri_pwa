<?php
declare(strict_types=1);

$query = $_SERVER['QUERY_STRING'] ?? '';
$destino = 'home.php' . ($query !== '' ? '?' . $query : '');
header('Location: ' . $destino, true, 301);
exit;
