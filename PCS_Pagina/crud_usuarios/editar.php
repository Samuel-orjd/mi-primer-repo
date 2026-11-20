<?php
require __DIR__ . '/includes/conexion.php';

$roles = ['cliente' => 'Cliente', 'administrador' => 'Administrador'];

$id = filter_var($_GET['id'] ?? $_POST['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: index.php');
    exit;
}

// No se carga contrasena_hash: el hash nunca se muestra ni se envía al formulario
$stmt = $pdo->prepare('SELECT id_usuario, nombre, apellido, correo, telefono, rol FROM usuarios WHERE id_usuario = :id');
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
    'rol'      => $usuario['rol'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($datos as $campo => $_) {
        $datos[$campo] = trim($_POST[$campo] ?? '');
    }
    $contrasena = $_POST['contrasena'] ?? '';   // opcional al editar

    if ($datos['nombre'] === '')   $errores[] = 'El nombre es obligatorio.';
    if ($datos['apellido'] === '') $errores[] = 'El apellido es obligatorio.';
    if (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) $errores[] = 'El correo no es válido.';
    if ($datos['telefono'] !== '' && !preg_match('/^[0-9+\s-]{7,20}$/', $datos['telefono'])) {
        $errores[] = 'El teléfono no es válido.';
    }
    if (!isset($roles[$datos['rol']])) $errores[] = 'El rol no es válido.';
    if ($contrasena !== '' && strlen($contrasena) < 8)  $errores[] = 'La nueva contraseña debe tener al menos 8 caracteres.';
    if ($contrasena !== '' && strlen($contrasena) > 72) $errores[] = 'La contraseña no puede superar 72 caracteres.';

    if (empty($errores)) {
        try {
            $sql = 'UPDATE usuarios
                    SET nombre = :nombre, apellido = :apellido, correo = :correo,
                        telefono = :telefono, rol = :rol';
            $params = [
                ':nombre'   => $datos['nombre'],
                ':apellido' => $datos['apellido'],
                ':correo'   => $datos['correo'],
                ':telefono' => $datos['telefono'] !== '' ? $datos['telefono'] : null,
                ':rol'      => $datos['rol'],
                ':id'       => $id,
            ];
            // Solo se cambia la contraseña si se escribió una nueva
            if ($contrasena !== '') {
                $sql .= ', contrasena_hash = :hash';
                $params[':hash'] = password_hash($contrasena, PASSWORD_DEFAULT);
            }
            $sql .= ' WHERE id_usuario = :id';

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            header('Location: index.php?msg=actualizado');
            exit;
        } catch (PDOException $e) {
            if (isset($e->errorInfo[1]) && (int) $e->errorInfo[1] === 1062) {
                $errores[] = 'Ya existe otro usuario con ese correo.';
            } else {
                $errores[] = 'Error al actualizar: ' . $e->getMessage();
            }
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<h2>Editar usuario #<?= (int) $id ?></h2>

<?php if ($errores): ?>
    <div class="errores"><ul>
        <?php foreach ($errores as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
    </ul></div>
<?php endif; ?>

<form class="formulario" method="post" action="editar.php">
    <input type="hidden" name="id" value="<?= (int) $id ?>">
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
        <label for="contrasena">Nueva contraseña (opcional)</label>
        <input type="password" id="contrasena" name="contrasena" minlength="8" maxlength="72" autocomplete="new-password">
        <small class="ayuda">Déjala en blanco para conservar la contraseña actual.</small>
    </div>
    <div class="botones">
        <button type="submit" class="btn">Actualizar</button>
        <a class="btn btn-claro" href="index.php">Cancelar</a>
    </div>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
