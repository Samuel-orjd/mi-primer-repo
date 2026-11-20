<?php
require __DIR__ . '/includes/conexion.php';

// Solo se permite eliminar por POST (evita borrados accidentales vía URL)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$msg = 'error_eliminar';
$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

if ($id) {
    try {
        $stmt = $pdo->prepare('DELETE FROM usuarios WHERE id_usuario = :id');
        $stmt->execute([':id' => $id]);
        $msg = $stmt->rowCount() > 0 ? 'eliminado' : 'error_no_existe';
    } catch (PDOException $e) {
        // 1451 = la fila tiene registros relacionados (direcciones, pedidos, etc.)
        $msg = (isset($e->errorInfo[1]) && (int) $e->errorInfo[1] === 1451)
            ? 'error_relaciones'
            : 'error_eliminar';
    }
}

header('Location: index.php?msg=' . $msg);
exit;
