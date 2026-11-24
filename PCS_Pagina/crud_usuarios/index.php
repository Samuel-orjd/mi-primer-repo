<?php
require __DIR__ . '/includes/conexion.php';

$usuarios = $pdo->query(
    'SELECT id_usuario, nombre, apellido, correo, telefono, rol, fecha_registro
     FROM usuarios
     ORDER BY id_usuario DESC'
)->fetchAll();

// Mensajes de éxito
$mensajes = [
    'creado'      => 'Usuario creado correctamente.',
    'actualizado' => 'Usuario actualizado correctamente.',
    'eliminado'   => 'Usuario eliminado correctamente.',
];
// Mensajes de error (se muestran con otro estilo)
$mensajes_error = [
    'error_relaciones' => 'No se puede eliminar: el usuario tiene direcciones, carritos, pedidos o reseñas asociados.',
    'error_no_existe'  => 'El usuario que intentas eliminar ya no existe.',
    'error_eliminar'   => 'No se pudo eliminar el usuario. Inténtalo de nuevo.',
];
$msg = $_GET['msg'] ?? '';

require __DIR__ . '/includes/header.php';
?>

<?php if (isset($mensajes[$msg])): ?>
    <div class="mensaje"><?= htmlspecialchars($mensajes[$msg]) ?></div>
<?php elseif (isset($mensajes_error[$msg])): ?>
    <div class="errores"><?= htmlspecialchars($mensajes_error[$msg]) ?></div>
<?php endif; ?>

<div class="barra">
    <h2>Listado de usuarios (<?= count($usuarios) ?>)</h2>
    <div>
        <a class="btn btn-claro" href="DASHBOARD.php">Ir al dashboard</a>
        <a class="btn" href="crear.php">Nuevo usuario</a>
    </div>
</div>

<?php if (empty($usuarios)): ?>
    <div class="vacio">No hay usuarios registrados.</div>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Correo</th>
                <th>Teléfono</th>
                <th>Rol</th>
                <th>Registro</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($usuarios as $u): ?>
            <tr>
                <td><?= (int) $u['id_usuario'] ?></td>
                <td><?= htmlspecialchars($u['nombre'] . ' ' . $u['apellido']) ?></td>
                <td><?= htmlspecialchars($u['correo']) ?></td>
                <td><?= htmlspecialchars($u['telefono'] ?? '—') ?></td>
                <td><?= htmlspecialchars(ucfirst($u['rol'])) ?></td>
                <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($u['fecha_registro']))) ?></td>
                <td class="acciones">
                    <a class="btn btn-claro btn-sm" href="editar.php?id=<?= (int) $u['id_usuario'] ?>">Editar</a>
                    <form method="post" action="eliminar.php"
                          onsubmit="return confirm('¿Eliminar este usuario?');">
                        <input type="hidden" name="id" value="<?= (int) $u['id_usuario'] ?>">
                        <button type="submit" class="btn btn-sm">Eliminar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
