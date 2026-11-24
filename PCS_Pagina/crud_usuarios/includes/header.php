<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($tituloPagina ?? 'Gestión de usuarios') ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<header class="encabezado">
    <div class="contenedor barra">
        <h1>PCS <span>Pagina</span></h1>
        <nav class="nav" aria-label="Navegación principal">
            <a href="DASHBOARD.php">Dashboard</a>
            <a href="index.php">Usuarios</a>
            <a href="productos.php">Productos</a>
            <a href="DASHBOARD.php#contacto">Contacto</a>
        </nav>
    </div>
</header>
<main class="contenedor">
