<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/admin_auth.php';
require_once dirname(__DIR__) . '/includes/admin_layout.php';

admin_sesion_iniciar();

if (admin_esta_logueado()) {
    header('Location: index.php');
    exit;
}

$error = '';
$usuarioRecordado = admin_usuario_recordado();
$recordarMarcado = admin_sesion_usar_prolongada();
$whatsappRecuperar = admin_enlace_recuperar_whatsapp($usuarioRecordado);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim((string) ($_POST['usuario'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $recordar = !empty($_POST['recordar']);

    if (admin_intentar_login($usuario, $password)) {
        admin_aplicar_sesion_tras_login($recordar, $usuario);
        header('Location: index.php');
        exit;
    }

    admin_guardar_usuario_recordado($usuario, $recordar);
    $usuarioRecordado = $recordar ? $usuario : '';
    $recordarMarcado = $recordar;
    $whatsappRecuperar = admin_enlace_recuperar_whatsapp($usuarioRecordado);
    $error = 'Usuario o contraseña incorrectos.';
}

admin_layout_inicio('Ingresar', false, 'admin-body admin-body--login');
?>
<div class="admin-login">
    <div class="admin-login__card">
        <div class="admin-login__avatar" aria-hidden="true">
            <svg width="72" height="72" viewBox="0 0 72 72" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="36" cy="36" r="36" fill="#1A1A1A"/>
                <circle cx="36" cy="28" r="10" fill="#FFFFFF"/>
                <path d="M16 58c0-11 9-18 20-18s20 7 20 18" fill="#FFFFFF"/>
            </svg>
        </div>

        <?php if ($error !== ''): ?>
            <div class="admin-alerta admin-alerta--error admin-login__alerta" role="alert"><?= h($error) ?></div>
        <?php endif; ?>

        <form class="admin-login__form" method="post" action="login.php" novalidate>
            <div class="admin-login__field">
                <input
                    type="text"
                    id="usuario"
                    name="usuario"
                    value="<?= h($usuarioRecordado) ?>"
                    placeholder="Usuario"
                    required
                    autocomplete="username"
                >
            </div>
            <div class="admin-login__field">
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Contraseña"
                    required
                    autocomplete="current-password"
                >
            </div>

            <div class="admin-login__opciones">
                <label class="admin-login__recordar">
                    <input type="checkbox" name="recordar" value="1"<?= $recordarMarcado ? ' checked' : '' ?>>
                    <span>Recuerdame</span>
                </label>
                <?php if ($whatsappRecuperar !== ''): ?>
                    <a
                        class="admin-login__olvido"
                        id="admin-login-recuperar"
                        href="<?= h($whatsappRecuperar) ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                    >¿Olvidaste tu contraseña?</a>
                <?php else: ?>
                    <span class="admin-login__olvido">¿Olvidaste tu contraseña?</span>
                <?php endif; ?>
            </div>

            <button type="submit" class="admin-login__submit">Inicio de sesión</button>
        </form>
    </div>
</div>
<?php if ($whatsappRecuperar !== ''): ?>
<script>
(function () {
    var enlace = document.getElementById('admin-login-recuperar');
    var usuario = document.getElementById('usuario');
    if (!enlace || !usuario) return;

    var numero = <?= json_encode(admin_whatsapp_soporte(), JSON_UNESCAPED_UNICODE) ?>;

    function actualizarEnlace() {
        var valor = usuario.value.trim();
        var mensaje = 'Hola, olvidé mi contraseña del panel admin LEODRI.';
        if (valor !== '') {
            mensaje += ' Mi usuario es: ' + valor;
        }
        enlace.href = 'https://wa.me/' + numero + '?text=' + encodeURIComponent(mensaje);
    }

    usuario.addEventListener('input', actualizarEnlace);
})();
</script>
<?php endif; ?>
<?php
admin_layout_fin();
