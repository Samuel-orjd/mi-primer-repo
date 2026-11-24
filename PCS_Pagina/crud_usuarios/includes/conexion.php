<?php
$host    = 'localhost';
$puerto  = '3306';
$db      = 'pcs_pagina';
$usuario = 'root';
$clave   = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$puerto;dbname=$db;charset=$charset";

$opciones = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $usuario, $clave, $opciones);
} catch (PDOException $e) {
    $detalle = $e->getMessage();

    if (strpos($detalle, '1049') !== false) {
        $ayuda = "La base de datos '{$db}' no existe todavía. Abre http://localhost/phpmyadmin, "
               . "ve a la pestaña Importar y carga el archivo database/pcs_pagina.sql.";
    } elseif (strpos($detalle, '2002') !== false || strpos($detalle, '2006') !== false) {
        $ayuda = "No se pudo conectar con MySQL. Abre el panel de XAMPP y pulsa Start en MySQL "
               . "(y revisa que el puerto sea $puerto).";
    } elseif (strpos($detalle, '1045') !== false) {
        $ayuda = "MySQL rechazó el usuario o la contraseña. Revisa \$usuario y \$clave en includes/conexion.php.";
    } else {
        $ayuda = 'No se pudo conectar con la base de datos.';
    }

    http_response_code(500);
    require __DIR__ . '/header.php';
    echo '<div class="errores"><strong>Error de conexión.</strong><br>'
       . htmlspecialchars($ayuda)
       . '<br><small>Detalle técnico: ' . htmlspecialchars($detalle) . '</small></div>';
    require __DIR__ . '/footer.php';
    exit;
}
