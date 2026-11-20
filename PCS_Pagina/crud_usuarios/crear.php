<?php
require __DIR__ . '/includes/conexion.php';

$errores = [];
$datos = ['nombre' => '', 'apellido' => '', 'correo' => '', 'telefono' => ''];

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
            $sql = 'INSERT INTO usuarios (nombre, apellido, correo, telefono) VALUES (:nombre, :apellido, :correo, :telefono)';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nombre'   => $datos['nombre'],
                ':apellido' => $datos['apellido'],
                ':correo'   => $datos['correo'],
                ':telefono' => $datos['telefono'] !== '' ? $datos['telefono'] : null,
            ]);
            header('Location: index.php?msg=creado');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $errores[] = 'Ya existe un usuario con ese correo.';
            } else {
                $errores[] = 'Error al guardar: ' . $e->getMessage();
            }
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<h2>Nuevo usuario</h2>

<?php if ($errores): ?>
    <div class="errores"><ul>
        <?php foreach ($errores as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
    </ul></div>
<?php endif; ?>

<form class="formulario" method="post" action="crear.php">
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
        <button type="submit" class="btn">Guardar</button>
        <a class="btn btn-claro" href="index.php">Cancelar</a>
    </div>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
