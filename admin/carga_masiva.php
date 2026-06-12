<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/admin_auth.php';
require_once dirname(__DIR__) . '/includes/importacion_masiva.php';
require_once dirname(__DIR__) . '/includes/admin_layout.php';

admin_requerir_login();

$resultado = $_SESSION['importacion_resultado'] ?? null;
unset($_SESSION['importacion_resultado']);

$pendientesFoto = productos_listar_sin_foto();
$columnas = importacion_columnas_etiquetas();

admin_layout_inicio('Carga masiva', true, 'admin-body--carga');
?>
<nav class="admin-subnav admin-subnav--stack" aria-label="Navegación">
    <a href="index.php">&larr; Dashboard</a>
    <a href="fotos_pendientes.php">Completar fotos (<?= count($pendientesFoto) ?>)</a>
</nav>

<?php if (isset($_GET['error']) && $_GET['error'] === 'csrf'): ?>
    <div class="admin-alerta admin-alerta--error">La sesión expiró. Vuelve a intentar.</div>
<?php elseif (isset($_GET['error']) && $_GET['error'] === 'vacio'): ?>
    <div class="admin-alerta admin-alerta--error">Agrega al menos un producto antes de importar.</div>
<?php endif; ?>

<?php if (is_array($resultado)): ?>
    <div class="admin-alerta admin-alerta--ok">
        Se importaron <strong><?= (int) $resultado['creados'] ?></strong> producto(s).
        <?php if ($resultado['creados'] > 0): ?>
            Ahora sube las fotos en <a href="fotos_pendientes.php">Completar fotos</a>.
        <?php endif; ?>
    </div>
    <?php if (!empty($resultado['errores'])): ?>
        <div class="admin-alerta admin-alerta--error">
            <p style="margin:0 0 8px;">Algunas filas no se importaron:</p>
            <ul class="admin-lista-errores">
                <?php foreach ($resultado['errores'] as $err): ?>
                    <li><?= h($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
<?php endif; ?>

<div class="admin-card admin-carga-intro">
    <h2 class="admin-carga-intro__titulo">Registro de productos masivo</h2>
    <p class="admin-hint admin-carga-intro__texto">
        Agrega productos uno a uno o pega desde Excel. Después importa y sube las fotos.
    </p>
    <ol class="admin-carga-intro__pasos">
        <li>Datos</li>
        <li>Importar</li>
        <li>Fotos</li>
    </ol>
    <a href="../plantillas/LEODRI_productos_simple.csv" class="admin-btn admin-btn--secundario admin-btn--bloque" download>
        Descargar plantilla CSV
    </a>
</div>

<div class="admin-card admin-carga-panel" id="carga-masiva-app">
    <div class="admin-carga-pegar">
        <label for="carga-pegar" class="admin-carga-pegar__label">Pegar desde Excel o Google Sheets</label>
        <textarea
            id="carga-pegar"
            class="admin-carga-pegar__area"
            rows="4"
            placeholder="Copia varias filas de tu hoja y pégalas aquí…"
        ></textarea>
        <div class="admin-carga-pegar__acciones">
            <button type="button" class="admin-btn admin-btn--secundario admin-btn--bloque" id="carga-btn-pegar">
                Agregar al listado
            </button>
            <label class="admin-btn admin-btn--secundario admin-btn--bloque admin-carga-archivo">
                Subir archivo CSV
                <input type="file" id="carga-archivo" accept=".csv,text/csv" hidden>
            </label>
        </div>
    </div>

    <div class="admin-carga-lista" id="carga-lista" aria-live="polite"></div>

    <button type="button" class="admin-btn admin-btn--secundario admin-btn--bloque admin-carga-add" id="carga-btn-fila">
        + Agregar producto
    </button>
</div>

<form method="post" action="carga_masiva_importar.php" id="carga-form-importar" class="admin-carga-sticky">
    <input type="hidden" name="csrf" value="<?= h(admin_csrf_token()) ?>">
    <input type="hidden" name="filas_json" id="carga-filas-json" value="">
    <p class="admin-carga-resumen" id="carga-resumen">0 productos listos</p>
    <button type="submit" class="admin-btn admin-btn--peligro admin-btn--bloque" id="carga-btn-importar" disabled>
        Importar productos
    </button>
</form>

<script src="js/carga_masiva.js" defer></script>
<?php
admin_layout_fin();
