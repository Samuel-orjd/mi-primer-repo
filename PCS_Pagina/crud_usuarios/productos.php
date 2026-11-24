<?php
// Inventario simple de productos: monitoreo + alta mínima.
require __DIR__ . '/includes/conexion.php';

$categorias = $pdo->query('SELECT id_categoria, nombre FROM categorias ORDER BY nombre')->fetchAll();
$idsCategoria = array_column($categorias, 'id_categoria');

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $idCategoria = filter_var($_POST['id_categoria'] ?? null, FILTER_VALIDATE_INT);
    $precio = filter_var($_POST['precio'] ?? '', FILTER_VALIDATE_FLOAT);
    $stock = filter_var($_POST['stock'] ?? '', FILTER_VALIDATE_INT);
    $imagen1 = trim($_POST['imagen1'] ?? '');
    $imagen2 = trim($_POST['imagen2'] ?? '');

    if ($nombre === '') $errores[] = 'El nombre es obligatorio.';
    if (!$idCategoria || !in_array((int) $idCategoria, $idsCategoria, true)) $errores[] = 'Categoría no válida.';
    if ($precio === false || $precio < 0) $errores[] = 'Precio no válido.';
    if ($stock === false || $stock < 0) $errores[] = 'Stock no válido.';
    if (strlen($imagen1) > 255 || strlen($imagen2) > 255) $errores[] = 'Ruta de imagen demasiado larga.';

    if (empty($errores)) {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare(
                'INSERT INTO productos (id_categoria, nombre, precio, stock, activo)
                 VALUES (:cat, :nombre, :precio, :stock, 1)'
            );
            $stmt->execute([
                ':cat' => (int) $idCategoria,
                ':nombre' => $nombre,
                ':precio' => $precio,
                ':stock' => (int) $stock,
            ]);
            $idProducto = (int) $pdo->lastInsertId();

            $stmtImg = $pdo->prepare('INSERT INTO imagenes_producto (id_producto, url, orden) VALUES (:id, :url, :orden)');
            foreach ([$imagen1, $imagen2] as $orden => $url) {
                if ($url !== '') {
                    $stmtImg->execute([':id' => $idProducto, ':url' => $url, ':orden' => $orden + 1]);
                }
            }
            $pdo->commit();
            header('Location: productos.php?msg=creado');
            exit;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errores[] = 'Error al guardar: ' . $e->getMessage();
        }
    }
}

$msg = $_GET['msg'] ?? '';
$productos = $pdo->query(
    'SELECT p.id_producto, p.nombre, p.precio, p.stock, p.activo, c.nombre AS categoria
     FROM productos p INNER JOIN categorias c ON c.id_categoria = p.id_categoria
     ORDER BY p.stock ASC, p.id_producto DESC'
)->fetchAll();

foreach ($productos as &$p) {
    $stmtImg = $pdo->prepare('SELECT url FROM imagenes_producto WHERE id_producto = :id ORDER BY orden, id_imagen LIMIT 2');
    $stmtImg->execute([':id' => $p['id_producto']]);
    $p['imagenes'] = $stmtImg->fetchAll(PDO::FETCH_COLUMN);
}
unset($p);

$totalProductos = count($productos);
$stockBajo = 0;
$valorInventario = 0;
foreach ($productos as $p) {
    if ((int) $p['stock'] <= 5) $stockBajo++;
    $valorInventario += (float) $p['precio'] * (int) $p['stock'];
}

$tituloPagina = 'Inventario';
require __DIR__ . '/includes/header.php';
?>

<div class="barra">
    <h2>Inventario de productos</h2>
    <a class="btn btn-claro" href="DASHBOARD.php">Ir al dashboard</a>
</div>

<?php if ($msg === 'creado'): ?>
    <div class="mensaje">Producto agregado al inventario.</div>
<?php endif; ?>

<?php if ($errores): ?>
    <div class="errores"><ul>
        <?php foreach ($errores as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
    </ul></div>
<?php endif; ?>

<section class="tarjetas">
    <article class="tarjeta"><h3>Productos</h3><p class="kpi"><?= $totalProductos ?></p></article>
    <article class="tarjeta"><h3>Stock bajo</h3><p class="kpi"><?= $stockBajo ?></p></article>
    <article class="tarjeta"><h3>Valor inventario</h3><p class="kpi">$ <?= number_format($valorInventario, 2, ',', '.') ?></p></article>
</section>

<section id="consolas">
    <h2>Vista previa (cambia el src por tus imágenes 200x200)</h2>
    <div class="espacios-img">
        <figure class="tarjeta-producto">
            <img class="imagen-producto" src="assets/img/producto-1.svg" width="200" height="200" alt="Imagen 1 del producto">
            <figcaption>Imagen 1 — 200 x 200</figcaption>
        </figure>
        <figure class="tarjeta-producto">
            <img class="imagen-producto" src="assets/img/producto-2.svg" width="200" height="200" alt="Imagen 2 del producto">
            <figcaption>Imagen 2 — 200 x 200</figcaption>
        </figure>
    </div>
</section>

<section>
    <h2>Listado</h2>
    <?php if (empty($productos)): ?>
        <div class="vacio">El inventario está vacío. Agrega el primer producto abajo.</div>
    <?php else: ?>
        <table>
            <thead><tr><th>ID</th><th>Imágenes</th><th>Producto</th><th>Categoría</th><th>Precio</th><th>Stock</th><th>Estado</th></tr></thead>
            <tbody>
            <?php foreach ($productos as $p): ?>
                <tr>
                    <td><?= (int) $p['id_producto'] ?></td>
                    <td>
                        <?php foreach ($p['imagenes'] as $url): ?>
                            <img class="thumb" src="<?= htmlspecialchars($url) ?>" alt="Imagen de <?= htmlspecialchars($p['nombre']) ?>">
                        <?php endforeach; ?>
                    </td>
                    <td><strong><?= htmlspecialchars($p['nombre']) ?></strong></td>
                    <td><?= htmlspecialchars($p['categoria']) ?></td>
                    <td>$ <?= number_format((float) $p['precio'], 2, ',', '.') ?></td>
                    <td class="<?= (int) $p['stock'] <= 5 ? 'stock-bajo' : '' ?>">
                        <?= (int) $p['stock'] ?>
                        <?php if ((int) $p['stock'] <= 5): ?><span class="alerta">Bajo</span><?php endif; ?>
                    </td>
                    <td><?= (int) $p['activo'] ? 'Activo' : 'Inactivo' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

<section>
    <h2>Agregar producto</h2>
    <form class="formulario" method="post" action="productos.php">
        <div class="campo">
            <label for="nombre">Nombre</label>
            <input type="text" id="nombre" name="nombre" maxlength="120" required>
        </div>
        <div class="campo">
            <label for="id_categoria">Categoría</label>
            <select id="id_categoria" name="id_categoria" required>
                <option value="">Selecciona</option>
                <?php foreach ($categorias as $c): ?>
                    <option value="<?= (int) $c['id_categoria'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo">
            <label for="precio">Precio</label>
            <input type="number" id="precio" name="precio" step="0.01" min="0" required>
        </div>
        <div class="campo">
            <label for="stock">Stock</label>
            <input type="number" id="stock" name="stock" step="1" min="0" value="0" required>
        </div>
        <div class="campo">
            <label for="imagen1">Imagen 1 (ruta o URL)</label>
            <input type="text" id="imagen1" name="imagen1" maxlength="255" placeholder="assets/img/mi-pc-1.jpg">
        </div>
        <div class="campo">
            <label for="imagen2">Imagen 2 (ruta o URL)</label>
            <input type="text" id="imagen2" name="imagen2" maxlength="255" placeholder="assets/img/mi-pc-2.jpg">
        </div>
        <div class="botones"><button type="submit" class="btn">Agregar</button></div>
    </form>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
