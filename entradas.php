<?php
require_once 'config.php';
requerirLogin();

$paginaActual = 'entradas.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>9Eleven - Entradas de mercancía</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php require 'includes/header.php'; ?>

<div class="contenedor-principal">
    <?php require 'includes/sidebar.php'; ?>

    <main class="contenido">
        <h1>Entradas de mercancía</h1>
        <p>Aquí se registrarán las entradas de mercancía de los proveedores.</p>
    </main>
</div>

<?php require 'includes/footer.php'; ?>
</body>
</html>