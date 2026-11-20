<?php
require __DIR__ . '/includes/conexion.php';

$roles = ['cliente' => 'Cliente', 'administrador' => 'Administrador'];

$errores = [];
$datos = ['nombre' => '', 'apellido' => '', 'correo' => '', 'telefono' => '', 'rol' => 'cliente'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($datos as $campo => $_) {
        $datos[$campo] = trim($_POST[$campo] ?? '');
    }
    $contrasena = $_POST['contrasena'] ?? '';   // la contraseña no se recorta con trim

    if ($datos['nombre'] === '')   $errores[] = 'El nombre es obligatorio.';
    if ($datos['apellido'] === '') $errores[] = 'El apellido es obligatorio.';
    if (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) $errores[] = 'El correo no es válido.';
    if ($datos['telefono'] !== '' && !preg_match('/^[0-9+\s-]{7,20}$/', $datos['telefono'])) {
        $errores[] = 'El teléfono no es válido.';
    }
    if (!isset($roles[$datos['rol']])) $errores[] = 'El rol no es válido.';
    if (strlen($contrasena) < 8)  $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
    if (strlen($contrasena) > 72) $errores[] = 'La contraseña no puede superar 72 caracteres.';

    if (empty($errores)) {
        try {
            $sql = 'INSERT INTO usuarios (nombre, apellido, correo, contrasena_hash, telefono, rol)
                    VALUES (:nombre, :apellido, :correo, :hash, :telefono, :rol)';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nombre'   => $datos['nombre'],
                ':apellido' => $datos['apellido'],
                ':correo'   => $datos['correo'],
                ':hash'     => password_hash($contrasena, PASSWORD_DEFAULT), // bcrypt, nunca texto plano
                ':telefono' => $datos['telefono'] !== '' ? $datos['telefono'] : null,
                ':rol'      => $datos['rol'],
            ]);
            header('Location: index.php?msg=creado');
            exit;
        } catch (PDOException $e) {
            if (isset($e->errorInfo[1]) && (int) $e->errorInfo[1] === 1062) {
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
    <div class="campo">
        <label for="rol">Rol</label>
        <select id="rol" name="rol" required>
            <?php foreach ($roles as $valor => $texto): ?>
                <option value="<?= $valor ?>"<?= $datos['rol'] === $valor ? ' selected' : '' ?>><?= $texto ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="campo">
        <label for="contrasena">Contraseña</label>
        <input type="password" id="contrasena" name="contrasena" minlength="8" maxlength="72" autocomplete="new-password" required>
        <small class="ayuda">Mínimo 8 caracteres. Se guarda cifrada (hash).</small>
    </div>
    <div class="botones">
        <button type="submit" class="btn">Guardar</button>
        <a class="btn btn-claro" href="index.php">Cancelar</a>
    </div>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
