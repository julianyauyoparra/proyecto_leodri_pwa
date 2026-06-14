<?php
declare(strict_types=1);

/** @var array $producto */
/** @var bool $esNuevo */

require_once __DIR__ . '/imagenes_producto.php';
require_once __DIR__ . '/repositorio_series.php';
require_once __DIR__ . '/categorias_tienda.php';

$inventarioStock = $producto['inventario_stock'] ?? [];
$series = series_listar();
$publicos = publicos_listar();
$serieActual = series_resolver_slug_producto((string) ($producto['serie'] ?? 'SERIE_JUVENIL'));
$publicoActual = (string) ($producto['publico'] ?? 'unisex');
$categoriaActual = categoria_desde_request((string) ($producto['categoria'] ?? CATEGORIA_HOME_DEFAULT));

$coloresLista = $producto['colores'] ?? [[]];
if ($coloresLista === []) {
    $coloresLista = [[]];
}
$colorPrincipal = imagenes_normalizar_color($coloresLista[0] ?? []);
$codigoPrincipal = (string) ($colorPrincipal['codigo'] ?? 'C1');
$stockColor = $inventarioStock[$codigoPrincipal] ?? [];
$variantesColor = $colorPrincipal['variantes'] ?? $colorPrincipal['tallas_disponibles'] ?? [];

$filasStock = [];
$stockMapaForm = $producto['stock_mapa'] ?? null;
foreach ($producto['tallas'] ?? [] as $talla) {
    $numero = trim((string) ($talla['numero'] ?? ''));
    if ($numero === '') {
        continue;
    }
    $filasStock[] = [
        'numero' => $numero,
        'pares' => (int) (is_array($stockMapaForm) ? ($stockMapaForm[$numero] ?? 0) : ($stockColor[$numero] ?? 0)),
        'disponible' => (bool) ($variantesColor[$numero] ?? $talla['disponible'] ?? true),
    ];
}
if ($filasStock === []) {
    $filasStock[] = ['numero' => '', 'pares' => 0, 'disponible' => true];
}

$guiaPdf = trim((string) ($producto['guia_pdf'] ?? ''));
?>
<form method="post" action="guardar.php" id="form-producto" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= h(admin_csrf_token()) ?>">
    <?php if (!$esNuevo): ?>
        <input type="hidden" name="id" value="<?= h((string) $producto['id']) ?>">
        <input type="hidden" name="serie_anterior" value="<?= h($serieActual) ?>">
    <?php endif; ?>

    <div class="admin-card">
        <h2>Identificación</h2>
        <p class="admin-hint" style="margin-top:0;">Datos del registro tal como aparecen en la tienda y en inventario.</p>

        <div class="admin-grid-2">
            <div class="admin-field">
                <label for="categoria">Categoría</label>
                <select id="categoria" name="categoria" required>
                    <?php foreach (categorias_tienda_nav() as $valor => $etiqueta): ?>
                        <option value="<?= h($valor) ?>" <?= $categoriaActual === $valor ? 'selected' : '' ?>><?= h($etiqueta) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="admin-field">
                <label for="publico">Público objetivo</label>
                <select id="publico" name="publico" required>
                    <?php foreach ($publicos as $publico): ?>
                        <option value="<?= h($publico['codigo']) ?>" <?= $publicoActual === $publico['codigo'] ? 'selected' : '' ?>>
                            <?= h($publico['etiqueta']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="admin-grid-2">
            <div class="admin-field">
                <label for="marca">Marca</label>
                <input type="text" id="marca" name="marca" required value="<?= h($producto['marca'] ?? '') ?>" placeholder="Ej. KidsFoot">
            </div>
            <div class="admin-field">
                <label for="modelo">Modelo</label>
                <input type="text" id="modelo" name="modelo" required value="<?= h($producto['modelo'] ?? $producto['nombre'] ?? '') ?>" placeholder="Ej. Spider Light">
            </div>
        </div>

        <div class="admin-grid-2">
            <div class="admin-field">
                <label for="tipo">Tipo</label>
                <input type="text" id="tipo" name="tipo" value="<?= h($producto['tipo'] ?? '') ?>" placeholder="Ej. Zapatillas">
            </div>
            <div class="admin-field">
                <label for="color-etiqueta">Color</label>
                <?php
                $etiquetaColor = (string) ($colorPrincipal['etiqueta'] ?? '');
                if ($etiquetaColor === '' || preg_match('/^Color \d+$/', $etiquetaColor)) {
                    $etiquetaColor = '';
                }
                ?>
                <input type="text" id="color-etiqueta" name="colores[etiqueta][]" value="<?= h($etiquetaColor) ?>" placeholder="Ej. Rojo, Negro" required>
            </div>
        </div>

        <div class="admin-field">
            <label for="serie">Serie de tallas</label>
            <select id="serie" name="serie" required>
                <?php foreach ($series as $serie): ?>
                    <?php $slug = (string) $serie['slug']; ?>
                    <option value="<?= h($slug) ?>" <?= $serieActual === $slug ? 'selected' : '' ?>>
                        <?= h((string) $serie['nombre']) ?> (<?= h((string) $serie['codigo_corto']) ?>, EU <?= (int) $serie['talla_min'] ?>–<?= (int) $serie['talla_max'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="admin-card">
        <h2>Precios</h2>
        <div class="admin-grid-2">
            <div class="admin-field">
                <label for="precio">Precio de venta (S/)</label>
                <input type="number" id="precio" name="precio" step="0.01" min="0.01" required value="<?= h((string) ($producto['precio'] ?? '')) ?>" placeholder="89.90">
            </div>
            <div class="admin-field">
                <label for="precio_anterior">Precio anterior (S/)</label>
                <input
                    type="number"
                    id="precio_anterior"
                    name="precio_anterior"
                    step="0.01"
                    min="0.01"
                    value="<?= h((string) ($producto['precio_anterior'] ?? '')) ?>"
                    placeholder="121.90"
                    data-auto-precio="1"
                >
                <p class="admin-hint">Sugerido automáticamente: +27&nbsp;% sobre el precio de venta.</p>
            </div>
        </div>
        <div class="admin-field admin-field--inline">
            <input type="checkbox" id="aplicar_descuento" name="aplicar_descuento" value="1" <?= !isset($producto['aplicar_descuento']) || $producto['aplicar_descuento'] ? 'checked' : '' ?>>
            <label for="aplicar_descuento">Mostrar descuento en la tienda (precio tachado y badge)</label>
        </div>
    </div>

    <div class="admin-card">
        <h2>Tallas y stock</h2>
        <p class="admin-hint" style="margin-top:0;margin-bottom:14px;">
            Pares en inventario y visibilidad en la tienda por talla EU.
        </p>
        <table class="admin-tabla admin-form-stock">
            <thead>
                <tr>
                    <th>Talla EU</th>
                    <th>Pares en stock</th>
                    <th>En tienda</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="stock-lista">
                <?php foreach ($filasStock as $filaStock): ?>
                    <tr data-stock-row>
                        <td>
                            <input type="text" name="stock_numero[]" value="<?= h($filaStock['numero']) ?>" class="admin-input-corto" inputmode="numeric" placeholder="39" required>
                        </td>
                        <td>
                            <input type="number" name="stock_pares[]" min="0" step="1" value="<?= (int) $filaStock['pares'] ?>" class="admin-input-corto" required>
                        </td>
                        <td>
                            <select name="stock_disponible[]">
                                <option value="1" <?= $filaStock['disponible'] ? 'selected' : '' ?>>Disponible</option>
                                <option value="0" <?= !$filaStock['disponible'] ? 'selected' : '' ?>>Agotada</option>
                            </select>
                        </td>
                        <td>
                            <button type="button" class="admin-btn admin-btn--secundario admin-btn--chico" data-remove-stock>Quitar</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <button type="button" class="admin-btn admin-btn--secundario admin-btn--chico" data-add-stock style="margin-top:12px;">+ Agregar talla</button>
    </div>

    <div class="admin-card">
        <h2>Detalles del producto</h2>
        <div class="admin-field">
            <label for="descripcion">Texto visible en «Detalles producto»</label>
            <textarea id="descripcion" name="descripcion" required placeholder="Descripción, características y beneficios"><?= h($producto['descripcion'] ?? '') ?></textarea>
        </div>
    </div>

    <div class="admin-card">
        <h2>Guía de tallas (PDF)</h2>
        <?php if ($guiaPdf !== ''): ?>
            <p class="admin-hint">
                PDF actual:
                <a href="../<?= h($guiaPdf) ?>" target="_blank" rel="noopener">Ver guía de tallas</a>
            </p>
        <?php else: ?>
            <p class="admin-hint">Este producto aún no tiene PDF de guía de tallas.</p>
        <?php endif; ?>
        <div class="admin-field">
            <label for="guia_pdf"><?= $guiaPdf !== '' ? 'Reemplazar PDF (opcional)' : 'Subir PDF' ?></label>
            <input type="file" id="guia_pdf" name="guia_pdf" accept="application/pdf,.pdf" <?= $esNuevo && $guiaPdf === '' ? 'required' : '' ?>>
            <p class="admin-hint">Referencia de formato: docs/FormatoGuiaTallas.md</p>
        </div>
    </div>

    <div class="admin-card">
        <h2>Imágenes</h2>
        <div id="colores-lista">
            <?php
            $tallasProducto = $producto['tallas'] ?? [];
            foreach ($coloresLista as $indiceColor => $color):
                $color = imagenes_normalizar_color($color);
                require __DIR__ . '/admin_form_color.php';
            endforeach;
            ?>
        </div>
        <button type="button" class="admin-btn admin-btn--secundario admin-btn--chico" data-add="color" hidden>+ Agregar otro color</button>
    </div>

    <div class="admin-acciones">
        <button type="submit" class="admin-btn admin-btn--primario">Guardar cambios</button>
        <a href="productos.php" class="admin-btn admin-btn--secundario">Cancelar</a>
        <?php if (!$esNuevo): ?>
            <button type="submit" formaction="eliminar.php" formmethod="post" class="admin-btn admin-btn--peligro" onclick="return confirm('¿Eliminar este producto?');">Eliminar</button>
        <?php endif; ?>
    </div>
</form>

<template id="tpl-stock-row">
    <tr data-stock-row>
        <td><input type="text" name="stock_numero[]" value="" class="admin-input-corto" inputmode="numeric" placeholder="39" required></td>
        <td><input type="number" name="stock_pares[]" min="0" step="1" value="0" class="admin-input-corto" required></td>
        <td>
            <select name="stock_disponible[]">
                <option value="1" selected>Disponible</option>
                <option value="0">Agotada</option>
            </select>
        </td>
        <td><button type="button" class="admin-btn admin-btn--secundario admin-btn--chico" data-remove-stock>Quitar</button></td>
    </tr>
</template>

<template id="tpl-color">
    <?php
    $color = imagenes_normalizar_color([]);
    $indiceColor = '__INDEX__';
    require __DIR__ . '/admin_form_color.php';
    ?>
</template>

<script src="js/admin.js"></script>
<script src="js/admin_producto.js"></script>
