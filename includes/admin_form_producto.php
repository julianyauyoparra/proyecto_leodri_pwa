<?php
declare(strict_types=1);

/** @var array $producto */
/** @var bool $esNuevo */

require_once __DIR__ . '/imagenes_producto.php';
require_once __DIR__ . '/series_tallas.php';

$bulletsTexto = implode("\n", $producto['bullets'] ?? []);
$tagsTexto = implode(', ', $producto['tags'] ?? []);
?>
<form method="post" action="guardar.php" id="form-producto" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= h(admin_csrf_token()) ?>">
    <?php if (!$esNuevo): ?>
        <input type="hidden" name="id" value="<?= h((string) $producto['id']) ?>">
        <input type="hidden" name="orden" value="<?= h((string) ($producto['orden'] ?? '0')) ?>">
    <?php endif; ?>

    <div class="admin-card">
        <h2>Datos del producto</h2>
        <div class="admin-grid-2">
            <div class="admin-field">
                <label for="marca">Marca</label>
                <input type="text" id="marca" name="marca" required value="<?= h($producto['marca'] ?? '') ?>" placeholder="Ej. KidsFoot">
            </div>
            <div class="admin-field">
                <label for="nombre">Nombre del zapato</label>
                <input type="text" id="nombre" name="nombre" required value="<?= h($producto['nombre'] ?? '') ?>" placeholder="Ej. Zapatillas Niño Spider Light">
            </div>
        </div>
        <div class="admin-field">
            <label for="descripcion">Descripción corta</label>
            <textarea id="descripcion" name="descripcion" required placeholder="Texto que verá el cliente en la ficha"><?= h($producto['descripcion'] ?? '') ?></textarea>
        </div>
        <div class="admin-field">
            <label for="precio">Precio de venta (S/)</label>
            <input type="number" id="precio" name="precio" step="0.01" min="0.01" required value="<?= h((string) ($producto['precio'] ?? '')) ?>" placeholder="89.90">
        </div>
        <div class="admin-grid-2">
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
                <p class="admin-hint">Por defecto se calcula 27&nbsp;% más que el precio de venta.</p>
            </div>
            <div class="admin-field">
                <label for="serie">Serie de tallas</label>
                <select id="serie" name="serie" required>
                    <?php
                    $serieActual = series_normalizar((string) ($producto['serie'] ?? 'escolar'));
                    foreach (series_opciones() as $valor => $etiqueta):
                        ?>
                        <option value="<?= h($valor) ?>" <?= $serieActual === $valor ? 'selected' : '' ?>><?= h($etiqueta) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="admin-hint">Define la curva de tallas y la guía del catálogo.</p>
            </div>
        </div>
        <div class="admin-field admin-field--inline">
            <input type="checkbox" id="aplicar_descuento" name="aplicar_descuento" value="1" <?= !isset($producto['aplicar_descuento']) || $producto['aplicar_descuento'] ? 'checked' : '' ?>>
            <label for="aplicar_descuento">Mostrar descuento en catálogo (precio tachado y badge)</label>
        </div>
        <div class="admin-field admin-field--inline">
            <input type="checkbox" id="activo" name="activo" value="1" <?= !isset($producto['activo']) || $producto['activo'] ? 'checked' : '' ?>>
            <label for="activo">Mostrar a clientes en el catálogo</label>
        </div>
        <div class="admin-field">
            <label for="bullets">Características destacadas</label>
            <textarea id="bullets" name="bullets" placeholder="Una por línea&#10;Luces LED&#10;Suela antideslizante"><?= h($bulletsTexto) ?></textarea>
            <p class="admin-hint">Opcional. Una característica por línea (máx. 3–4 recomendado).</p>
        </div>
        <div class="admin-grid-2">
            <div class="admin-field">
                <label for="categoria">Categoría principal</label>
                <select id="categoria" name="categoria" required>
                    <?php
                    require_once __DIR__ . '/categorias_tienda.php';
                    $categoriaActual = categoria_desde_request((string) ($producto['categoria'] ?? CATEGORIA_HOME_DEFAULT));
                    foreach (categorias_tienda_nav() as $valor => $etiqueta):
                        ?>
                        <option value="<?= h($valor) ?>" <?= $categoriaActual === $valor ? 'selected' : '' ?>><?= h($etiqueta) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="admin-hint">Define en qué sección del menú y la home aparece el producto.</p>
            </div>
            <div class="admin-field">
                <label for="tags">Etiquetas adicionales</label>
                <input type="text" id="tags" name="tags" value="<?= h($tagsTexto) ?>" placeholder="Niño, Escolar, Running">
                <p class="admin-hint">Opcional. Separadas por coma. Ej: Niño, Casual</p>
            </div>
        </div>
    </div>

    <div class="admin-card">
        <h2>Colores del producto</h2>
        <p class="admin-hint" style="margin-top:0;margin-bottom:14px;">
            Cada color es una variante con su propia foto. El cliente elegirá el color tocando la miniatura.
            Sube las <strong>6 fotos por color</strong> en una sola selección (cada archivo debe incluir en el nombre la vista: derecha, izquierda, frente, posterior, arriba o abajo). La de <strong>Derecha</strong> es la miniatura del catálogo.
        </p>
        <div id="colores-lista">
            <?php
            $coloresLista = $producto['colores'] ?? [[]];
            if ($coloresLista === []) {
                $coloresLista = [[]];
            }
            $tallasProducto = $producto['tallas'] ?? [];
            foreach ($coloresLista as $indiceColor => $color):
                $color = imagenes_normalizar_color($color);
                require __DIR__ . '/admin_form_color.php';
            endforeach;
            ?>
        </div>
        <button type="button" class="admin-btn admin-btn--secundario admin-btn--chico" data-add="color">+ Agregar otro color</button>
    </div>

    <div class="admin-card">
        <h2>Tallas y stock</h2>
        <div id="tallas-lista">
            <?php foreach ($producto['tallas'] ?? [['numero' => '', 'disponible' => true]] as $talla): ?>
                <?php require __DIR__ . '/admin_form_talla.php'; ?>
            <?php endforeach; ?>
        </div>
        <button type="button" class="admin-btn admin-btn--secundario admin-btn--chico" data-add="talla">+ Agregar talla</button>
    </div>

    <div class="admin-card">
        <h2>Beneficios (opcional)</h2>
        <p class="admin-hint" style="margin-top:0;margin-bottom:14px;">Aparecen al pulsar «Ver Beneficios» en la ficha.</p>
        <div id="beneficios-lista">
            <?php
            $beneficiosLista = $producto['beneficios'] ?? [];
            if ($beneficiosLista === []) {
                $beneficiosLista = [[]];
            }
            foreach ($beneficiosLista as $beneficio):
                require __DIR__ . '/admin_form_beneficio.php';
            endforeach;
            ?>
        </div>
        <button type="button" class="admin-btn admin-btn--secundario admin-btn--chico" data-add="beneficio">+ Agregar beneficio</button>
    </div>

    <div class="admin-acciones">
        <button type="submit" class="admin-btn admin-btn--primario">Guardar producto</button>
        <a href="productos.php" class="admin-btn admin-btn--secundario">Cancelar</a>
        <?php if (!$esNuevo): ?>
            <button type="submit" formaction="eliminar.php" formmethod="post" class="admin-btn admin-btn--peligro" onclick="return confirm('¿Eliminar este producto?');">Eliminar</button>
        <?php endif; ?>
    </div>
</form>

<template id="tpl-color">
    <?php
    $color = imagenes_normalizar_color([]);
    $indiceColor = '__INDEX__';
    $tallasProducto = $producto['tallas'] ?? [];
    require __DIR__ . '/admin_form_color.php';
    ?>
</template>
<template id="tpl-talla">
    <?php $talla = ['numero' => '', 'disponible' => true]; require __DIR__ . '/admin_form_talla.php'; ?>
</template>
<template id="tpl-beneficio">
    <?php $beneficio = []; require __DIR__ . '/admin_form_beneficio.php'; ?>
</template>

<script src="js/admin.js"></script>
<script src="js/admin_producto.js"></script>
