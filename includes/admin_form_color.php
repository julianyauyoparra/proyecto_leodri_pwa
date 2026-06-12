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
$codigoInv = $color['codigo_inventario'] ?? '';
if ($codigoInv === '' && !empty($color['sku_sin_talla'])) {
    $codigoInv = preg_replace('/-\d+$/', '', $color['sku_sin_talla']) ?? '';
}
?>
<div class="admin-repeater admin-repeater--color" data-repeater="color">
    <button type="button" class="admin-repeater__quitar" data-remove>Quitar color</button>
    <h3 class="admin-repeater__titulo">Color <?= is_numeric($indiceColor) ? ((int) $indiceColor + 1) : '' ?></h3>

    <div class="admin-field">
        <label>Código de inventario</label>
        <input
            type="text"
            name="colores[codigo_inventario][]"
            value="<?= h($codigoInv) ?>"
            placeholder="Ej. KDF-SPL-RJN (opcional)"
        >
        <p class="admin-hint">Para SKU y pedidos por WhatsApp. Si lo dejas vacío, se genera automáticamente.</p>
    </div>

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
            Fotos del color (6 vistas)
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
            Selecciona las 6 imágenes a la vez. Cada archivo debe incluir en el nombre la vista:
            <strong>derecha</strong>, <strong>izquierda</strong>, <strong>frente</strong>,
            <strong>posterior</strong>, <strong>arriba</strong> o <strong>abajo</strong>
            (por ejemplo <code>modelo_derecha.jpg</code>).
        </p>
        <ul class="admin-upload-lote__avisos" hidden></ul>
    </div>

    <?php
    $tallasProducto = $tallasProducto ?? [];
    $variantesColor = $color['variantes'] ?? $color['tallas_disponibles'] ?? [];
    $tallasConNumero = array_values(array_filter($tallasProducto, static function (array $talla): bool {
        return trim((string) ($talla['numero'] ?? '')) !== '';
    }));
    ?>
    <?php if ($tallasConNumero !== []): ?>
        <div class="admin-variantes">
            <p class="admin-variantes__titulo">Stock por talla (este color)</p>
            <p class="admin-hint">Marca «Agotada» solo la combinación color + talla vendida. Ej. SKU REF-C1-36.</p>
            <div class="admin-variantes__grid">
                <?php foreach ($tallasConNumero as $talla): ?>
                    <?php
                    $numeroTalla = (string) ($talla['numero'] ?? '');
                    $disponibleVariante = $variantesColor[$numeroTalla] ?? !empty($talla['disponible']);
                    ?>
                    <label class="admin-variante">
                        <span class="admin-variante__num">Talla <?= h($numeroTalla) ?></span>
                        <select name="colores[variantes][<?= h((string) $indiceColor) ?>][<?= h($numeroTalla) ?>]">
                            <option value="1" <?= $disponibleVariante ? 'selected' : '' ?>>Disponible</option>
                            <option value="0" <?= !$disponibleVariante ? 'selected' : '' ?>>Agotada</option>
                        </select>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
