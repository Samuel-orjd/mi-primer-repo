<?php
// =====================================================================
//  Panel de control: usuarios + monitoreo de productos
//  Todas las consultas van antes de imprimir HTML.
// =====================================================================
require __DIR__ . '/includes/conexion.php';

$totalUsuarios = (int) $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
$productosActivos = (int) $pdo->query('SELECT COUNT(*) FROM productos WHERE activo = 1')->fetchColumn();
$stockBajo = (int) $pdo->query('SELECT COUNT(*) FROM productos WHERE activo = 1 AND stock <= 5')->fetchColumn();
$pedidosPendientes = (int) $pdo->query("SELECT COUNT(*) FROM pedidos WHERE estado = 'pendiente'")->fetchColumn();

$productos = $pdo->query(
    'SELECT p.id_producto, p.nombre, p.marca, p.precio, p.stock, p.activo, p.destacado,
            c.nombre AS categoria
     FROM productos p
     INNER JOIN categorias c ON c.id_categoria = p.id_categoria
     ORDER BY p.stock ASC, p.id_producto DESC'
)->fetchAll();

$usuariosRecientes = $pdo->query(
    'SELECT id_usuario, nombre, apellido, correo, rol, fecha_registro
     FROM usuarios
     ORDER BY id_usuario DESC
     LIMIT 5'
)->fetchAll();

$tituloPagina = 'Panel PCS';
require __DIR__ . '/includes/header.php';
?>

<section class="barra">
    <h2>Panel de control</h2>
    <div>
        <a class="btn btn-claro" href="index.php">Usuarios</a>
        <a class="btn btn-claro" href="productos.php">Productos</a>
        <a class="btn" href="crear_producto.php">Nuevo producto</a>
    </div>
</section>

<section id="resumen" class="tarjetas">
    <article class="tarjeta">
        <h3>Usuarios</h3>
        <p class="kpi"><?= $totalUsuarios ?></p>
        <a class="enlace" href="index.php">Ver usuarios</a>
    </article>
    <article class="tarjeta">
        <h3>Productos activos</h3>
        <p class="kpi"><?= $productosActivos ?></p>
        <a class="enlace" href="productos.php">Ver productos</a>
    </article>
    <article class="tarjeta">
        <h3>Stock bajo</h3>
        <p class="kpi"><?= $stockBajo ?></p>
        <a class="enlace" href="productos.php">Revisar inventario</a>
    </article>
    <article class="tarjeta">
        <h3>Pedidos pendientes</h3>
        <p class="kpi"><?= $pedidosPendientes ?></p>
        <span class="ayuda">Estado: pendiente</span>
    </article>
</section>

<section id="productos">
    <div class="barra">
        <h2>Monitoreo de productos</h2>
        <a class="btn btn-claro btn-sm" href="crear_producto.php">Nuevo producto</a>
    </div>

    <?php if (empty($productos)): ?>
        <div class="vacio">No hay productos registrados todavía. Crea el primero con el botón «Nuevo producto».</div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Producto</th>
                    <th>Categoría</th>
                    <th>Precio</th>
                    <th>Stock</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($productos as $p): ?>
                <?php $stockBajoFila = (int) $p['stock'] <= 5; ?>
                <tr>
                    <td><?= (int) $p['id_producto'] ?></td>
                    <td>
                        <strong><?= htmlspecialchars($p['nombre']) ?></strong>
                        <?php if (!empty($p['marca'])): ?>
                            <br><small><?= htmlspecialchars($p['marca']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($p['categoria']) ?></td>
                    <td>$ <?= number_format((float) $p['precio'], 2, ',', '.') ?></td>
                    <td class="<?= $stockBajoFila ? 'stock-bajo' : '' ?>">
                        <?= (int) $p['stock'] ?>
                        <?php if ($stockBajoFila): ?>
                            <span class="alerta">Bajo</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="estado <?= (int) $p['activo'] ? 'activo' : 'inactivo' ?>">
                            <?= (int) $p['activo'] ? 'Activo' : 'Inactivo' ?>
                        </span>
                        <?php if ((int) $p['destacado']): ?>
                            <span class="estado destacado">Destacado</span>
                        <?php endif; ?>
                    </td>
                    <td class="acciones">
                        <a class="btn btn-claro btn-sm" href="editar_producto.php?id=<?= (int) $p['id_producto'] ?>">Editar</a>
                        <form method="post" action="eliminar_producto.php"
                              onsubmit="return confirm('¿Eliminar este producto?');">
                            <input type="hidden" name="id" value="<?= (int) $p['id_producto'] ?>">
                            <button type="submit" class="btn btn-sm">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

<section id="consolas">
    <div class="barra">
        <h2>PCs y consolas en venta</h2>
        <span class="ayuda">Reemplaza la ruta <code>src</code> de cada imagen por la tuya (200 x 200 px).</span>
    </div>

    <!-- Espacios listos para tus 2 imágenes de 200x200 -->
    <div class="espacios-img">
        <figure class="tarjeta-producto">
            <!-- IMG 1: cambia src="assets/img/producto-1.svg" por tu imagen -->
            <img class="imagen-producto"
                 src="assets/img/producto-1.svg"
                 width="200" height="200"
                 alt="Imagen 1 del producto, 200 por 200 píxeles">
            <figcaption>Imagen 1 — 200 x 200</figcaption>
        </figure>

        <figure class="tarjeta-producto">
            <!-- IMG 2: cambia src="assets/img/producto-2.svg" por tu imagen -->
            <img class="imagen-producto"
                 src="assets/img/producto-2.svg"
                 width="200" height="200"
                 alt="Imagen 2 del producto, 200 por 200 píxeles">
            <figcaption>Imagen 2 — 200 x 200</figcaption>
        </figure>
    </div>
</section>

<section id="usuarios-recientes">
    <div class="barra">
        <h2>Usuarios recientes</h2>
        <a class="btn btn-claro btn-sm" href="index.php">Ver todos</a>
    </div>

    <?php if (empty($usuariosRecientes)): ?>
        <div class="vacio">No hay usuarios registrados.</div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Rol</th>
                    <th>Registro</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($usuariosRecientes as $u): ?>
                <tr>
                    <td><?= (int) $u['id_usuario'] ?></td>
                    <td><?= htmlspecialchars($u['nombre'] . ' ' . $u['apellido']) ?></td>
                    <td><?= htmlspecialchars($u['correo']) ?></td>
                    <td><?= htmlspecialchars(ucfirst($u['rol'])) ?></td>
                    <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($u['fecha_registro']))) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

<section id="contacto">
    <h2>Contáctanos</h2>
    <p>Correo: contacto@pcs.example</p>
    <p class="ayuda">Los mensajes se guardarán en la tabla <code>mensajes_contacto</code> cuando se active el formulario.</p>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
