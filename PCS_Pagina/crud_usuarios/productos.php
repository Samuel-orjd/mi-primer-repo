<?php
require __DIR__ . '/includes/conexion.php';

$categorias = $pdo->query('SELECT id_categoria, nombre FROM categorias ORDER BY nombre')->fetchAll();

$categoriaId = filter_var($_GET['categoria'] ?? null, FILTER_VALIDATE_INT);
$q = trim($_GET['q'] ?? '');

$where = [];
$params = [];

if ($categoriaId) {
    $where[] = 'p.id_categoria = :categoria';
    $params[':categoria'] = $categoriaId;
}

if ($q !== '') {
    $where[] = '(p.nombre LIKE :q OR p.marca LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}

$sql = 'SELECT p.id_producto, p.nombre, p.marca, p.precio, p.stock, p.activo, p.destacado,
               c.nombre AS categoria
        FROM productos p
        INNER JOIN categorias c ON c.id_categoria = p.id_categoria';

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY p.id_producto DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$productos = $stmt->fetchAll();

// Hasta 2 imágenes por producto, en el orden guardado.
foreach ($productos as &$p) {
    $stmtImg = $pdo->prepare(
        'SELECT url FROM imagenes_producto WHERE id_producto = :id ORDER BY orden, id_imagen LIMIT 2'
    );
    $stmtImg->execute([':id' => $p['id_producto']]);
    $p['imagenes'] = $stmtImg->fetchAll(PDO::FETCH_COLUMN);
}
unset($p);

$mensajes = [
    'creado'      => 'Producto creado correctamente.',
    'actualizado' => 'Producto actualizado correctamente.',
    'eliminado'   => 'Producto eliminado correctamente.',
];
$mensajes_error = [
    'error_no_existe'  => 'El producto que intentas eliminar ya no existe.',
    'error_relaciones' => 'No se puede eliminar: el producto aparece en pedidos o carritos.',
    'error_eliminar'   => 'No se pudo eliminar el producto. Inténtalo de nuevo.',
];
$msg = $_GET['msg'] ?? '';

$tituloPagina = 'Productos';
require __DIR__ . '/includes/header.php';
?>

<?php if (isset($mensajes[$msg])): ?>
    <div class="mensaje"><?= htmlspecialchars($mensajes[$msg]) ?></div>
<?php elseif (isset($mensajes_error[$msg])): ?>
    <div class="errores"><?= htmlspecialchars($mensajes_error[$msg]) ?></div>
<?php endif; ?>

<div class="barra">
    <h2>Listado de productos (<?= count($productos) ?>)</h2>
    <div>
        <a class="btn btn-claro" href="DASHBOARD.php">Ir al dashboard</a>
        <a class="btn" href="crear_producto.php">Nuevo producto</a>
    </div>
</div>

<form class="filtros" method="get" action="productos.php">
    <div class="campo">
        <label for="categoria">Categoría</label>
        <select id="categoria" name="categoria">
            <option value="">Todas</option>
            <?php foreach ($categorias as $c): ?>
                <option value="<?= (int) $c['id_categoria'] ?>"
                    <?= $categoriaId === (int) $c['id_categoria'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="campo">
        <label for="q">Buscar por nombre o marca</label>
        <input type="text" id="q" name="q" maxlength="120" value="<?= htmlspecialchars($q) ?>" placeholder="Ej: RTX, Gamer...">
    </div>
    <button type="submit" class="btn">Filtrar</button>
    <a class="btn btn-claro" href="productos.php">Limpiar</a>
</form>

<?php if (empty($productos)): ?>
    <div class="vacio">No hay productos registrados con esos filtros.</div>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Imágenes</th>
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
                    <?php if (!empty($p['imagenes'])): ?>
                        <?php foreach ($p['imagenes'] as $url): ?>
                            <img class="thumb" src="<?= htmlspecialchars($url) ?>" alt="Imagen de <?= htmlspecialchars($p['nombre']) ?>">
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="ayuda">Sin imagen</span>
                    <?php endif; ?>
                </td>
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
                    <?php if ($stockBajoFila): ?><span class="alerta">Bajo</span><?php endif; ?>
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

<?php require __DIR__ . '/includes/footer.php'; ?>
