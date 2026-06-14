<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/admin_auth.php';
require_once dirname(__DIR__) . '/includes/carga_individual.php';
require_once dirname(__DIR__) . '/includes/importacion_masiva.php';
require_once dirname(__DIR__) . '/includes/repositorio_productos.php';
require_once dirname(__DIR__) . '/includes/series_tallas.php';
require_once dirname(__DIR__) . '/includes/imagenes_producto.php';
require_once dirname(__DIR__) . '/includes/admin_layout.php';

admin_requerir_login();

if (isset($_GET['cancelar'])) {
    $borradorCancel = $_SESSION['admin_carga_individual'] ?? null;
    if (is_array($borradorCancel) && !empty($borradorCancel['token'])) {
        carga_limpiar_tmp((string) $borradorCancel['token']);
    }
    unset($_SESSION['admin_carga_individual']);
    header('Location: carga_individual.php');
    exit;
}

$paso = $_GET['paso'] ?? 'upload';
$borrador = $_SESSION['admin_carga_individual'] ?? null;
$error = '';
$duplicadoId = null;
$exito = '';
$errores = $_SESSION['admin_errores'] ?? [];
unset($_SESSION['admin_errores']);
$duplicadoIdValidacion = isset($_SESSION['admin_carga_duplicado_id'])
    ? (int) $_SESSION['admin_carga_duplicado_id']
    : null;
unset($_SESSION['admin_carga_duplicado_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'analizar') {
    if (!admin_verificar_csrf($_POST['csrf'] ?? null)) {
        $error = 'Sesión inválida. Recarga la página e intenta de nuevo.';
    } else {
        $tipoLote = ($_POST['tipo_lote'] ?? '') === 'media_docena' ? 'media_docena' : 'docena';
        $tipoMedia = in_array($_POST['tipo_media'] ?? '', ['central', 'chica', 'grande'], true)
            ? (string) $_POST['tipo_media']
            : 'central';
        $publico = trim((string) ($_POST['publico'] ?? 'joven'));

        if (!publico_es_valido($publico)) {
            $error = 'Seleccione un público objetivo válido.';
        } elseif (!isset($_FILES['imagenes'])) {
            $error = 'Debe seleccionar las 6 imágenes.';
        } else {
            $resultado = carga_procesar_upload($_FILES['imagenes'], $tipoLote, $tipoMedia);
            if (!$resultado['ok']) {
                $error = $resultado['error'];
                $duplicadoId = isset($resultado['duplicado_id']) ? (int) $resultado['duplicado_id'] : null;
            } else {
                $_SESSION['admin_carga_individual'] = [
                    'token' => $resultado['token'],
                    'datos' => $resultado['datos'],
                    'distribucion' => $resultado['distribucion'],
                    'archivos_tmp' => $resultado['archivos_tmp'],
                    'tipo_lote' => $resultado['tipo_lote'],
                    'tipo_media' => $resultado['tipo_media'],
                    'publico' => $publico,
                ];
                header('Location: carga_individual.php?paso=validacion');
                exit;
            }
        }
    }
}

if ($paso === 'validacion' && !is_array($borrador)) {
    header('Location: carga_individual.php');
    exit;
}

$publicos = publicos_listar();
$series = series_listar();

admin_layout_inicio('Carga individual', true, 'admin-body--carga-individual');
?>

<nav class="admin-subnav">
    <a href="index.php">Dashboard</a>
    <span aria-hidden="true">/</span>
    <span>Carga individual</span>
</nav>

<?php if ($error !== ''): ?>
    <div class="admin-alerta admin-alerta--error">
        <p style="margin:0;"><?= h($error) ?></p>
        <?php if ($duplicadoId !== null && $duplicadoId > 0): ?>
            <p style="margin:8px 0 0;">
                <a href="producto.php?id=<?= $duplicadoId ?>">Editar producto existente (ID <?= (int) $duplicadoId ?>)</a>
                ·
                <a href="productos.php">Ver listado</a>
            </p>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($errores !== []): ?>
    <div class="admin-alerta admin-alerta--error">
        <?php foreach ($errores as $mensajeError): ?>
            <p style="margin:0;"><?= h($mensajeError) ?></p>
        <?php endforeach; ?>
        <?php if ($duplicadoIdValidacion !== null && $duplicadoIdValidacion > 0): ?>
            <p style="margin:8px 0 0;">
                <a href="producto.php?id=<?= $duplicadoIdValidacion ?>">Editar producto existente (ID <?= $duplicadoIdValidacion ?>)</a>
                ·
                <a href="productos.php">Ver listado</a>
            </p>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($paso === 'upload'): ?>
    <div class="admin-card admin-carga-individual">
        <h2>Subir imágenes del producto</h2>
        <p class="admin-hint">
            Seleccione las <strong>6 imágenes</strong> (una por vista). Solo la foto <strong>Derecha</strong> lleva el nombre completo;
            las demás se nombran únicamente con la vista.
        </p>
        <p class="admin-carga-individual__ejemplo"><code><?= h(carga_nomenclatura_ejemplo()) ?></code></p>
        <p class="admin-hint">Las otras 5 imágenes:</p>
        <p class="admin-carga-individual__ejemplo"><code>Izquierda.webp · Frente.webp · Posterior.webp · Arriba.webp · Abajo.webp</code></p>
        <ul class="admin-carga-individual__lista">
            <li>Nomenclatura completa (solo Derecha): Categoría, Marca, Modelo, Tipo, Color, Serie, Precio, Derecha</li>
            <li>Códigos de serie: <?php
                $codigos = array_map(static fn ($s) => $s['codigo_corto'], $series);
            echo h(implode(', ', $codigos)); ?> (Sh = CAB)</li>
        </ul>

        <form method="post" enctype="multipart/form-data" class="admin-carga-individual__form">
            <input type="hidden" name="csrf" value="<?= h(admin_csrf_token()) ?>">
            <input type="hidden" name="accion" value="analizar">

            <div class="admin-field">
                <label for="imagenes">Imágenes del color (6 archivos)</label>
                <input type="file" id="imagenes" name="imagenes[]" accept="image/jpeg,image/png,image/webp" multiple required>
            </div>

            <div class="admin-grid-2">
                <div class="admin-field">
                    <label for="tipo_lote">Tipo de lote</label>
                    <select id="tipo_lote" name="tipo_lote" required data-tipo-lote>
                        <option value="docena">1 docena (12 pares)</option>
                        <option value="media_docena" selected>½ docena (6 pares)</option>
                    </select>
                </div>
                <div class="admin-field" data-media-wrap>
                    <label for="tipo_media">Curva de media docena</label>
                    <select id="tipo_media" name="tipo_media">
                        <option value="central">Central (estándar)</option>
                        <option value="chica">Chica</option>
                        <option value="grande">Grande</option>
                    </select>
                </div>
            </div>

            <div class="admin-field">
                <label for="publico">Público objetivo</label>
                <select id="publico" name="publico" required>
                    <?php foreach ($publicos as $p): ?>
                        <option value="<?= h($p['codigo']) ?>" <?= ($p['codigo'] ?? '') === 'joven' ? 'selected' : '' ?>><?= h($p['etiqueta']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="admin-btn admin-btn--primario">Analizar imágenes</button>
        </form>
    </div>

<?php else:
    $datos = $borrador['datos'];
    $distribucion = $borrador['distribucion'];
    $serie = series_obtener_por_codigo_corto($datos['serie_codigo']);
    $especiales = $serie['tallas_especiales'] ?? [];
    ?>
    <div class="admin-card admin-carga-individual">
        <h2>Validación del responsable</h2>
        <p class="admin-hint">Revise los datos extraídos del nombre de archivo, complete los detalles y suba el PDF de la guía de tallas.</p>

        <dl class="admin-carga-individual__resumen">
            <div><dt>Categoría</dt><dd><?= h($datos['categoria_texto']) ?> (<?= h($datos['categoria']) ?>)</dd></div>
            <div><dt>Marca</dt><dd><?= h($datos['marca']) ?></dd></div>
            <div><dt>Modelo</dt><dd><?= h($datos['modelo']) ?></dd></div>
            <div><dt>Tipo</dt><dd><?= h($datos['tipo']) ?></dd></div>
            <div><dt>Color</dt><dd><?= h($datos['color']) ?></dd></div>
            <div><dt>Serie</dt><dd><?= h($datos['serie_codigo']) ?> — <?= h($datos['serie_nombre']) ?></dd></div>
            <div><dt>Precio</dt><dd>S/ <?= h(number_format((float) $datos['precio'], 2)) ?></dd></div>
            <div><dt>Precio anterior</dt><dd>S/ <?= h(number_format(producto_precio_anterior_sugerido((float) $datos['precio']), 2)) ?> (auto +27 %)</dd></div>
            <div><dt>Lote</dt><dd><?= $borrador['tipo_lote'] === 'docena' ? '1 docena' : '½ docena (' . h($borrador['tipo_media']) . ')' ?></dd></div>
            <div><dt>Público</dt><dd><?= h($borrador['publico']) ?></dd></div>
        </dl>

        <h3 class="admin-carga-individual__sub">Tallas y cantidades (pares)</h3>
        <form method="post" action="carga_individual_guardar.php" enctype="multipart/form-data" class="admin-carga-individual__form" id="form-validacion">
            <input type="hidden" name="csrf" value="<?= h(admin_csrf_token()) ?>">

            <table class="admin-tabla admin-carga-individual__tabla">
                <thead>
                    <tr>
                        <th>Talla EU</th>
                        <th>Cantidad (pares)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($distribucion as $talla => $cantidad): ?>
                        <tr>
                            <td><?= h((string) $talla) ?></td>
                            <td>
                                <input type="number" name="cantidad[<?= h((string) $talla) ?>]" min="0" step="1"
                                    value="<?= (int) $cantidad ?>" class="admin-input-corto" required>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($especiales !== []): ?>
                <div class="admin-carga-individual__especiales" data-especiales>
                    <h3 class="admin-carga-individual__sub">Tallas especiales (opcional)</h3>
                    <p class="admin-hint">Permitidas para esta serie: <?= h(implode(', ', array_map('strval', $especiales))) ?></p>
                    <div data-especiales-lista></div>
                    <button type="button" class="admin-btn admin-btn--secundario admin-btn--chico" data-add-especial>
                        + Agregar talla especial
                    </button>
                    <template id="tpl-especial">
                        <div class="admin-carga-individual__especial-fila">
                            <input type="number" name="especial_numero[]" min="1" max="99" placeholder="Talla" class="admin-input-corto">
                            <input type="number" name="especial_cantidad[]" min="1" step="1" value="1" placeholder="Pares" class="admin-input-corto">
                            <button type="button" class="admin-btn admin-btn--secundario admin-btn--chico" data-remove-especial>Quitar</button>
                        </div>
                    </template>
                </div>
            <?php endif; ?>

            <div class="admin-field">
                <label for="descripcion">Detalles del producto</label>
                <textarea id="descripcion" name="descripcion" required placeholder="Descripción, características y beneficios visibles en la ficha"></textarea>
            </div>

            <div class="admin-field">
                <label for="guia_pdf">Guía de tallas — PDF (<?= h($datos['serie_nombre']) ?>)</label>
                <input type="file" id="guia_pdf" name="guia_pdf" accept="application/pdf,.pdf" required>
                <p class="admin-hint">Suba el PDF ya formateado (tabla de equivalencias e instrucciones). Referencia: docs/FormatoGuiaTallas.md</p>
            </div>

            <div class="admin-acciones">
                <button type="submit" class="admin-btn admin-btn--primario">Confirmar y cargar producto</button>
                <a href="carga_individual.php?cancelar=1" class="admin-btn admin-btn--secundario">Cancelar</a>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php
admin_layout_fin();
?>

<script src="js/carga_individual.js"></script>
