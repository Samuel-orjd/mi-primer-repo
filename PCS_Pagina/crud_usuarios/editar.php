<?php
require __DIR__ . '/includes/conexion.php';

$id = filter_var($_GET['id'] ?? $_POST['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id = :id');
$stmt->execute([':id' => $id]);
$usuario = $stmt->fetch();

if (!$usuario) {
    header('Location: index.php');
    exit;
}

$errores = [];
$datos = [
    'nombre'   => $usuario['nombre'],
    'apellido' => $usuario['apellido'],
    'correo'   => $usuario['correo'],
    'telefono' => $usuario['telefono'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($datos as $campo => $_) {
        $datos[$campo] = trim($_POST[$campo] ?? '');
    }

    if ($datos['nombre'] === '')   $errores[] = 'El nombre es obligatorio.';
    if ($datos['apellido'] === '') $errores[] = 'El apellido es obligatorio.';
    if (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) $errores[] = 'El correo no es válido.';
    if ($datos['telefono'] !== '' && !preg_match('/^[0-9+\s-]{7,20}$/', $datos['telefono'])) {
        $errores[] = 'El teléfono no es válido.';
    }

    if (empty($errores)) {
        try {
            $sql = 'UPDATE usuarios
                    SET nombre = :nombre, apellido = :apellido, correo = :correo, telefono = :telefono
                    WHERE id = :id';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nombre'   => $datos['nombre'],
                ':apellido' => $datos['apellido'],
                ':correo'   => $datos['correo'],
                ':telefono' => $datos['telefono'] !== '' ? $datos['telefono'] : null,
                ':id'       => $id,
            ]);
            header('Location: index.php?msg=actualizado');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $errores[] = 'Ya existe otro usuario con ese correo.';
            } else {
                $errores[] = 'Error al actualizar: ' . $e->getMessage();
            }
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<h2>Editar usuario #<?= $id ?></h2>

<?php if ($errores): ?>
    <div class="errores"><ul>
        <?php foreach ($errores as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
    </ul></div>
<?php endif; ?>

<form class="formulario" method="post" action="editar.php">
    <input type="hidden" name="id" value="<?= $id ?>">
    <div class="campo">
        <label for="nombre">Nombre</label>
        <input type="text" id="nombre" name="nombre" maxlength="80" value="<?= htmlspecialchars($datos['nombre']) ?>" required>
    </div>
    <div class="campo">
        <label for="apellido">Apellido</label>
        <input type="text" id="apellido" name="apellido" maxlength="80" value="<?= htmlspecialchars($datos['apellido']) ?>" required>
    </div>
    <div class="campo">
        <label for="correo">Correo electrónico</label>
        <input type="email" id="correo" name="correo" maxlength="120" value="<?= htmlspecialchars($datos['correo']) ?>" required>
    </div>
    <div class="campo">
        <label for="telefono">Teléfono (opcional)</label>
        <input type="text" id="telefono" name="telefono" maxlength="20" value="<?= htmlspecialchars($datos['telefono']) ?>">
    </div>
    <div class="botones">
        <button type="submit" class="btn">Actualizar</button>
        <a class="btn btn-claro" href="index.php">Cancelar</a>
    </div>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
