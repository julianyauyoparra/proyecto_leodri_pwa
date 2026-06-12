<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin_auth.php';

admin_cerrar_sesion();
header('Location: login.php');
exit;
