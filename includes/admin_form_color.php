<?php
declare(strict_types=1);

/** @var array $color */
/** @var int|string $indiceColor */

require_once __DIR__ . '/imagenes_producto.php';

$indiceColor = $indiceColor ?? 0;
$imagenes = $color['imagenes'] ?? [];
if (!is_array($imagenes)) {
    $imagenes = [];
}
$codigoColor = (string) ($color['codigo'] ?? ('C' . ((int) $indiceColor + 1)));
?>
<div class="admin-repeater admin-repeater--color" data-repeater="color">
    <?php if (is_numeric($indiceColor) && (int) $indiceColor > 0): ?>
        <button type="button" class="admin-repeater__quitar" data-remove>Quitar color</button>
    <?php endif; ?>
    <h3 class="admin-repeater__titulo"><?= is_numeric($indiceColor) && (int) $indiceColor > 0 ? 'Color ' . ((int) $indiceColor + 1) : 'Imágenes del producto' ?></h3>

    <input type="hidden" name="colores[codigo][]" value="<?= h($codigoColor) ?>">

    <?php if (is_numeric($indiceColor) && (int) $indiceColor > 0): ?>
        <div class="admin-field">
            <label>Color</label>
            <input type="text" name="colores[etiqueta][]" value="<?= h($color['etiqueta'] ?? '') ?>" placeholder="Ej. Rojo" required>
        </div>
    <?php endif; ?>

    <div class="admin-upload-grid" data-upload-previews>
        <?php foreach (imagenes_vistas_etiquetas() as $vista => $etiqueta): ?>
            <?php $rutaActual = $imagenes[$vista] ?? ''; ?>
            <div class="admin-upload-item<?= $rutaActual === '' ? ' admin-upload-item--vacio' : '' ?>" data-vista="<?= h($vista) ?>">
                <span class="admin-upload-item__label">
                    <?= h($etiqueta) ?>
                    <?php if ($vista === 'derecha'): ?><span class="admin-upload-item__req">*</span><?php endif; ?>
                </span>
                <?php if ($rutaActual !== ''): ?>
                    <img class="admin-upload-item__preview" src="../<?= h($rutaActual) ?>" alt="">
                    <input type="hidden" name="colores[imagen_actual][<?= h((string) $indiceColor) ?>][<?= h($vista) ?>]" value="<?= h($rutaActual) ?>">
                <?php else: ?>
                    <span class="admin-upload-item__placeholder" aria-hidden="true"></span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="admin-field admin-upload-lote">
        <label class="admin-upload-lote__label" for="colores-lote-<?= h((string) $indiceColor) ?>">
            Reemplazar fotos (opcional)
        </label>
        <input
            type="file"
            id="colores-lote-<?= h((string) $indiceColor) ?>"
            name="colores[<?= h((string) $indiceColor) ?>][lote][]"
            accept="image/jpeg,image/png,image/webp"
            multiple
            class="admin-upload-lote__input"
        >
        <p class="admin-hint">
            Sube solo si quieres cambiar imágenes. Cada archivo debe incluir la vista en el nombre:
            <strong>derecha</strong>, <strong>izquierda</strong>, <strong>frente</strong>,
            <strong>posterior</strong>, <strong>arriba</strong> o <strong>abajo</strong>.
        </p>
        <ul class="admin-upload-lote__avisos" hidden></ul>
    </div>
</div>
