<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/admin_auth.php';
require_once dirname(__DIR__) . '/includes/repositorio_productos.php';
require_once dirname(__DIR__) . '/includes/admin_layout.php';

admin_requerir_login();

$productos = productos_listar_admin();
$guardado = isset($_GET['guardado']);
$eliminado = isset($_GET['eliminado']);
$csrf = admin_csrf_token();

admin_layout_inicio('Productos', true, 'admin-body--productos');
?>
<nav class="admin-subnav admin-subnav--stack" aria-label="Navegación">
    <a href="index.php">&larr; Dashboard</a>
    <a href="carga_masiva.php">Carga masiva</a>
    <a href="fotos_pendientes.php">Completar fotos</a>
</nav>

<?php if ($guardado): ?>
    <div class="admin-alerta admin-alerta--ok">Producto guardado correctamente.</div>
<?php endif; ?>
<?php if ($eliminado): ?>
    <div class="admin-alerta admin-alerta--ok">Producto eliminado.</div>
<?php endif; ?>

<div class="admin-card admin-productos-intro">
    <h2 class="admin-productos-intro__titulo">Productos cargados (<?= count($productos) ?>)</h2>
    <p class="admin-hint admin-productos-intro__texto">
        En la tienda se muestran todos los productos con foto. Los que falten completar aparecen en «Completar fotos».
    </p>
</div>

<?php if ($productos === []): ?>
    <div class="admin-card admin-productos-vacio">
        <p>No hay productos todavía.</p>
        <a href="producto.php" class="admin-btn admin-btn--primario admin-btn--bloque">Crear primer producto</a>
        <a href="carga_masiva.php" class="admin-btn admin-btn--secundario admin-btn--bloque">Carga masiva</a>
    </div>
<?php else: ?>
    <div class="admin-productos-lista">
        <?php foreach ($productos as $p):
            $id = (int) $p['id'];
            $tieneFoto = producto_tiene_foto_catalogo($id);
            $nombreCompleto = trim($p['marca'] . ' ' . $p['nombre']);
            ?>
            <article class="admin-producto-item">
                <div class="admin-producto-item__info">
                    <p class="admin-producto-item__meta">ID <?= h((string) $id) ?></p>
                    <h3 class="admin-producto-item__nombre"><?= h($nombreCompleto) ?></h3>
                    <p class="admin-producto-item__precio"><?= h(formatear_precio((float) $p['precio'])) ?></p>
                    <div class="admin-producto-item__badges">
                        <?php if (!$tieneFoto): ?>
                            <span class="admin-badge admin-badge--foto">Sin foto</span>
                        <?php else: ?>
                            <span class="admin-badge admin-badge--activo">En tienda</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="admin-producto-item__acciones">
                    <a href="producto.php?id=<?= $id ?>" class="admin-btn admin-btn--primario admin-btn--chico admin-btn--bloque">
                        Editar
                    </a>
                    <?php if (!$tieneFoto): ?>
                        <a href="fotos_pendientes.php" class="admin-btn admin-btn--secundario admin-btn--chico admin-btn--bloque">
                            Subir foto
                        </a>
                    <?php elseif ($tieneFoto): ?>
                        <a href="../home.php#tienda-productos" class="admin-btn admin-btn--secundario admin-btn--chico admin-btn--bloque" target="_blank" rel="noopener">
                            Ver en tienda
                        </a>
                    <?php endif; ?>
                    <form
                        method="post"
                        action="eliminar.php"
                        class="admin-producto-item__eliminar"
                        onsubmit="return confirm('¿Eliminar este producto? Esta acción no se puede deshacer.');"
                    >
                        <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <button type="submit" class="admin-btn admin-btn--peligro admin-btn--chico admin-btn--bloque">
                            Eliminar
                        </button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<a href="producto.php" class="admin-productos-nuevo admin-btn admin-btn--primario admin-btn--bloque">
    + Nuevo producto
</a>
<?php
admin_layout_fin();
