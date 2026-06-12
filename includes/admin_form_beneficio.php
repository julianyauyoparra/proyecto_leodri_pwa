<?php
declare(strict_types=1);

/** @var array $beneficio */
?>
<div class="admin-repeater" data-repeater="beneficio">
    <button type="button" class="admin-repeater__quitar" data-remove>Quitar</button>
    <div class="admin-field">
        <label>Título del beneficio</label>
        <input type="text" name="beneficios[titulo][]" value="<?= h($beneficio['titulo'] ?? '') ?>" placeholder="Ej. Suela antideslizante">
    </div>
    <div class="admin-field">
        <label>Descripción</label>
        <textarea name="beneficios[texto][]" placeholder="Explica por qué es un beneficio para el cliente"><?= h($beneficio['texto'] ?? '') ?></textarea>
    </div>
</div>
