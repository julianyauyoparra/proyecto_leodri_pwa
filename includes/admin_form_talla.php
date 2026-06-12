<?php
declare(strict_types=1);

/** @var array $talla */
$disponible = !isset($talla['disponible']) || $talla['disponible'];
?>
<div class="admin-repeater" data-repeater="talla">
    <button type="button" class="admin-repeater__quitar" data-remove>Quitar</button>
    <div class="admin-grid-2">
        <div class="admin-field">
            <label>Número de talla</label>
            <input type="text" name="tallas[numero][]" value="<?= h((string) ($talla['numero'] ?? '')) ?>" placeholder="22">
        </div>
        <div class="admin-field">
            <label>¿Hay stock?</label>
            <select name="tallas[disponible][]">
                <option value="1" <?= $disponible ? 'selected' : '' ?>>Sí, disponible</option>
                <option value="0" <?= !$disponible ? 'selected' : '' ?>>No, agotada</option>
            </select>
        </div>
    </div>
</div>
