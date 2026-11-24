<?php
require __DIR__ . '/includes/conexion.php';

$id = filter_var($_GET['id'] ?? $_POST['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: productos.php');
    exit;
}

$categorias = $pdo->query('SELECT id_categoria, nombre FROM categorias ORDER BY nombre')->fetchAll();
$idsCategoria = array_column($categorias, 'id_categoria');

$stmt = $pdo->prepare('SELECT * FROM productos WHERE id_producto = :id');
$stmt->execute([':id' => $id]);
$producto = $stmt->fetch();

if (!$producto) {
    header('Location: productos.php');
    exit;
}

$stmtImg = $pdo->prepare(
    'SELECT url FROM imagenes_producto WHERE id_producto = :id ORDER BY orden, id_imagen LIMIT 2'
);
$stmtImg->execute([':id' => $id]);
$imagenes = $stmtImg->fetchAll(PDO::FETCH_COLUMN);

$campos = [
    'id_categoria', 'nombre', 'marca', 'descripcion', 'precio', 'stock',
    'procesador', 'tarjeta_grafica', 'memoria_ram_gb', 'almacenamiento',
    'placa_madre', 'fuente_poder', 'gabinete', 'garantia_meses',
    'imagen1', 'imagen2',
];

$datos = [];
foreach ($campos as $campo) {
    $datos[$campo] = $producto[$campo] ?? '';
}
$datos['imagen1'] = $imagenes[0] ?? '';
$datos['imagen2'] = $imagenes[1] ?? '';

$destacado = (int) $producto['destacado'] === 1;
$activo = (int) $producto['activo'] === 1;
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($campos as $campo) {
        $datos[$campo] = trim($_POST[$campo] ?? '');
    }
    $destacado = isset($_POST['destacado']);
    $activo = isset($_POST['activo']);

    $idCategoria = filter_var($datos['id_categoria'], FILTER_VALIDATE_INT);
    if (!$idCategoria || !in_array((int) $idCategoria, $idsCategoria, true)) {
        $errores[] = 'Selecciona una categoría válida.';
    }

    if ($datos['nombre'] === '') {
        $errores[] = 'El nombre del producto es obligatorio.';
    }

    $precio = filter_var($datos['precio'], FILTER_VALIDATE_FLOAT);
    if ($precio === false || $precio < 0) {
        $errores[] = 'El precio debe ser un número mayor o igual a cero.';
    }

    $stock = filter_var($datos['stock'], FILTER_VALIDATE_INT);
    if ($stock === false || $stock < 0) {
        $errores[] = 'El stock debe ser un entero mayor o igual a cero.';
    }

    if ($datos['memoria_ram_gb'] !== '') {
        $ram = filter_var($datos['memoria_ram_gb'], FILTER_VALIDATE_INT);
        if ($ram === false || $ram < 0) {
            $errores[] = 'La memoria RAM debe ser un entero válido.';
        }
    }

    if ($datos['garantia_meses'] !== '') {
        $garantia = filter_var($datos['garantia_meses'], FILTER_VALIDATE_INT);
        if ($garantia === false || $garantia < 0) {
            $errores[] = 'La garantía debe ser un entero válido.';
        }
    }

    if (strlen($datos['imagen1']) > 255 || strlen($datos['imagen2']) > 255) {
        $errores[] = 'La ruta de las imágenes no puede superar 255 caracteres.';
    }

    if (empty($errores)) {
        try {
            $pdo->beginTransaction();

            $sql = 'UPDATE productos SET
                        id_categoria = :id_categoria,
                        nombre = :nombre,
                        marca = :marca,
                        descripcion = :descripcion,
                        precio = :precio,
                        stock = :stock,
                        procesador = :procesador,
                        tarjeta_grafica = :tarjeta_grafica,
                        memoria_ram_gb = :memoria_ram_gb,
                        almacenamiento = :almacenamiento,
                        placa_madre = :placa_madre,
                        fuente_poder = :fuente_poder,
                        gabinete = :gabinete,
                        garantia_meses = :garantia_meses,
                        destacado = :destacado,
                        activo = :activo
                    WHERE id_producto = :id';

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id'              => $id,
                ':id_categoria'    => (int) $idCategoria,
                ':nombre'          => $datos['nombre'],
                ':marca'           => $datos['marca'] !== '' ? $datos['marca'] : null,
                ':descripcion'     => $datos['descripcion'] !== '' ? $datos['descripcion'] : null,
                ':precio'          => $precio,
                ':stock'           => (int) $stock,
                ':procesador'      => $datos['procesador'] !== '' ? $datos['procesador'] : null,
                ':tarjeta_grafica' => $datos['tarjeta_grafica'] !== '' ? $datos['tarjeta_grafica'] : null,
                ':memoria_ram_gb'  => $datos['memoria_ram_gb'] !== '' ? (int) $datos['memoria_ram_gb'] : null,
                ':almacenamiento'  => $datos['almacenamiento'] !== '' ? $datos['almacenamiento'] : null,
                ':placa_madre'     => $datos['placa_madre'] !== '' ? $datos['placa_madre'] : null,
                ':fuente_poder'    => $datos['fuente_poder'] !== '' ? $datos['fuente_poder'] : null,
                ':gabinete'        => $datos['gabinete'] !== '' ? $datos['gabinete'] : null,
                ':garantia_meses'  => $datos['garantia_meses'] !== '' ? (int) $datos['garantia_meses'] : null,
                ':destacado'       => $destacado ? 1 : 0,
                ':activo'          => $activo ? 1 : 0,
            ]);

            $pdo->prepare('DELETE FROM imagenes_producto WHERE id_producto = :id')->execute([':id' => $id]);

            $stmtImg = $pdo->prepare(
                'INSERT INTO imagenes_producto (id_producto, url, orden) VALUES (:id, :url, :orden)'
            );
            foreach ([$datos['imagen1'], $datos['imagen2']] as $orden => $url) {
                if ($url !== '') {
                    $stmtImg->execute([
                        ':id'    => $id,
                        ':url'   => $url,
                        ':orden' => $orden + 1,
                    ]);
                }
            }

            $pdo->commit();
            header('Location: productos.php?msg=actualizado');
            exit;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errores[] = 'Error al actualizar el producto: ' . $e->getMessage();
        }
    }
}

$tituloPagina = 'Editar producto';
require __DIR__ . '/includes/header.php';
?>

<h2>Editar producto #<?= (int) $id ?></h2>

<?php if ($errores): ?>
    <div class="errores"><ul>
        <?php foreach ($errores as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
    </ul></div>
<?php endif; ?>

<form class="formulario" method="post" action="editar_producto.php">
    <input type="hidden" name="id" value="<?= (int) $id ?>">

    <div class="campo">
        <label for="id_categoria">Categoría</label>
        <select id="id_categoria" name="id_categoria" required>
            <option value="">Selecciona una categoría</option>
            <?php foreach ($categorias as $c): ?>
                <option value="<?= (int) $c['id_categoria'] ?>"
                    <?= (string) $c['id_categoria'] === $datos['id_categoria'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="campo">
        <label for="nombre">Nombre</label>
        <input type="text" id="nombre" name="nombre" maxlength="120" value="<?= htmlspecialchars($datos['nombre']) ?>" required>
    </div>

    <div class="campo">
        <label for="marca">Marca</label>
        <input type="text" id="marca" name="marca" maxlength="60" value="<?= htmlspecialchars($datos['marca']) ?>">
    </div>

    <div class="campo">
        <label for="descripcion">Descripción</label>
        <textarea id="descripcion" name="descripcion" maxlength="2000"><?= htmlspecialchars($datos['descripcion']) ?></textarea>
    </div>

    <div class="campo">
        <label for="precio">Precio</label>
        <input type="number" id="precio" name="precio" step="0.01" min="0" value="<?= htmlspecialchars($datos['precio']) ?>" required>
    </div>

    <div class="campo">
        <label for="stock">Stock</label>
        <input type="number" id="stock" name="stock" step="1" min="0" value="<?= htmlspecialchars($datos['stock']) ?>" required>
    </div>

    <div class="campo">
        <label for="procesador">Procesador</label>
        <input type="text" id="procesador" name="procesador" maxlength="80" value="<?= htmlspecialchars($datos['procesador']) ?>">
    </div>

    <div class="campo">
        <label for="tarjeta_grafica">Tarjeta gráfica</label>
        <input type="text" id="tarjeta_grafica" name="tarjeta_grafica" maxlength="80" value="<?= htmlspecialchars($datos['tarjeta_grafica']) ?>">
    </div>

    <div class="campo">
        <label for="memoria_ram_gb">Memoria RAM (GB)</label>
        <input type="number" id="memoria_ram_gb" name="memoria_ram_gb" step="1" min="0" value="<?= htmlspecialchars($datos['memoria_ram_gb']) ?>">
    </div>

    <div class="campo">
        <label for="almacenamiento">Almacenamiento</label>
        <input type="text" id="almacenamiento" name="almacenamiento" maxlength="80" value="<?= htmlspecialchars($datos['almacenamiento']) ?>">
    </div>

    <div class="campo">
        <label for="placa_madre">Placa madre</label>
        <input type="text" id="placa_madre" name="placa_madre" maxlength="80" value="<?= htmlspecialchars($datos['placa_madre']) ?>">
    </div>

    <div class="campo">
        <label for="fuente_poder">Fuente de poder</label>
        <input type="text" id="fuente_poder" name="fuente_poder" maxlength="60" value="<?= htmlspecialchars($datos['fuente_poder']) ?>">
    </div>

    <div class="campo">
        <label for="gabinete">Gabinete</label>
        <input type="text" id="gabinete" name="gabinete" maxlength="80" value="<?= htmlspecialchars($datos['gabinete']) ?>">
    </div>

    <div class="campo">
        <label for="garantia_meses">Garantía (meses)</label>
        <input type="number" id="garantia_meses" name="garantia_meses" step="1" min="0" value="<?= htmlspecialchars($datos['garantia_meses']) ?>">
    </div>

    <div class="campo">
        <label for="imagen1">Imagen 1 (ruta o URL, 200 x 200)</label>
        <input type="text" id="imagen1" name="imagen1" maxlength="255" value="<?= htmlspecialchars($datos['imagen1']) ?>" placeholder="assets/img/mi-pc-1.jpg">
    </div>

    <div class="campo">
        <label for="imagen2">Imagen 2 (ruta o URL, 200 x 200)</label>
        <input type="text" id="imagen2" name="imagen2" maxlength="255" value="<?= htmlspecialchars($datos['imagen2']) ?>" placeholder="assets/img/mi-pc-2.jpg">
    </div>

    <div class="campo">
        <label><input type="checkbox" name="destacado" value="1" <?= $destacado ? 'checked' : '' ?>> Destacado</label>
        <label><input type="checkbox" name="activo" value="1" <?= $activo ? 'checked' : '' ?>> Activo</label>
    </div>

    <div class="botones">
        <button type="submit" class="btn">Actualizar producto</button>
        <a class="btn btn-claro" href="productos.php">Cancelar</a>
    </div>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
