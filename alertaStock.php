<?php
require_once 'config.php';
requerirLogin();

$paginaActual = 'alertaStock.php';

$productos = $pdo->query(
    "SELECT nombreProducto, stockActual, stockMinimo
     FROM productos
     WHERE stockActual <= stockMinimo
     ORDER BY (stockMinimo - stockActual) DESC"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>9Eleven - Alerta de Stock</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php require 'includes/header.php'; ?>

<div class="contenedor-principal">
    <?php require 'includes/sidebar.php'; ?>

    <main class="contenido">
        <h1>Alerta de Stock</h1>

        <div class="panel">
            <div class="panel-header">
                <h2>Productos con stock bajo</h2>
            </div>

            <?php if (empty($productos)): ?>
                <p class="sin-datos">No hay productos con stock bajo en este momento.</p>
            <?php else: ?>
                <?php foreach ($productos as $p):
                    $critico = $p['stockActual'] == 0;
                ?>
                    <div class="alerta-item">
                        <div class="info">
                            <b><?= h($p['nombreProducto']) ?></b>
                            <span>Stock actual: <?= (int)$p['stockActual'] ?> und — Mínimo: <?= (int)$p['stockMinimo'] ?> und</span>
                        </div>
                        <span class="alerta-tag"><?= $critico ? 'CRÍTICO' : 'BAJO' ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require 'includes/footer.php'; ?>
</body>
</html>
