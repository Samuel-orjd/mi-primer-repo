<?php
require __DIR__ . '/includes/conexion.php';

// Solo se permite eliminar por POST (evita borrados accidentales vía URL).
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: productos.php');
    exit;
}

$msg = 'error_eliminar';
$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

if ($id) {
    try {
        $pdo->beginTransaction();

        $pdo->prepare('DELETE FROM imagenes_producto WHERE id_producto = :id')->execute([':id' => $id]);

        $stmt = $pdo->prepare('DELETE FROM productos WHERE id_producto = :id');
        $stmt->execute([':id' => $id]);
        $msg = $stmt->rowCount() > 0 ? 'eliminado' : 'error_no_existe';

        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        // 1451 = la fila tiene registros relacionados (pedidos, carritos, etc.).
        $msg = (isset($e->errorInfo[1]) && (int) $e->errorInfo[1] === 1451)
            ? 'error_relaciones'
            : 'error_eliminar';
    }
}

header('Location: productos.php?msg=' . $msg);
exit;
