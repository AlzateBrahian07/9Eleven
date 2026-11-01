<?php
require_once 'config.php';
requerirLogin();

$paginaActual = 'dashboard.php';

// --- KPI: total de productos registrados ---
$totalProductos = $pdo->query("SELECT COUNT(*) FROM productos")->fetchColumn();

// --- KPI: cantidad de productos con stock bajo (stockActual <= stockMinimo) ---
$alertasStock = $pdo->query(
    "SELECT COUNT(*) FROM productos WHERE stockActual <= stockMinimo"
)->fetchColumn();

// --- KPI: proveedores activos (total registrados, no hay campo "estado" en la BD) ---
$totalProveedores = $pdo->query("SELECT COUNT(*) FROM proveedores")->fetchColumn();

// --- KPI: ventas de hoy ---
$ventasHoy = $pdo->prepare(
    "SELECT COALESCE(SUM(totalVenta), 0) FROM ventas WHERE fechaVenta = CURDATE()"
);
$ventasHoy->execute();
$totalVentasHoy = $ventasHoy->fetchColumn();

// --- Movimientos recientes: últimas ventas (detalle) ---
$movimientos = $pdo->query(
    "SELECT p.nombreProducto, dv.cantidad, v.fechaVenta
     FROM detalle_ventas dv
     JOIN ventas v ON v.ID_Venta = dv.ID_Venta
     JOIN productos p ON p.ID_Producto = dv.ID_Producto
     ORDER BY v.fechaVenta DESC, dv.ID_Detalle DESC
     LIMIT 6"
)->fetchAll();

// --- Alertas de stock bajo (para el panel lateral) ---
$alertasLista = $pdo->query(
    "SELECT nombreProducto, stockActual
     FROM productos
     WHERE stockActual <= stockMinimo
     ORDER BY stockActual ASC
     LIMIT 6"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>9Eleven - Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php require 'includes/header.php'; ?>

<div class="contenedor-principal">
    <?php require 'includes/sidebar.php'; ?>

    <main class="contenido">
        <h1>Dashboard</h1>

        <div class="tarjetas">
            <div class="tarjeta">
                <div class="etiqueta">PRODUCTOS</div>
                <div class="valor"><?= (int)$totalProductos ?></div>
                <div class="sub">registrados</div>
            </div>
            <div class="tarjeta">
                <div class="etiqueta">STOCK</div>
                <div class="valor"><?= (int)$alertasStock ?></div>
                <div class="sub">alertas</div>
            </div>
            <div class="tarjeta">
                <div class="etiqueta">PROVEEDORES</div>
                <div class="valor"><?= (int)$totalProveedores ?></div>
                <div class="sub">activos</div>
            </div>
            <div class="tarjeta">
                <div class="etiqueta">VENTAS HOY</div>
                <div class="valor"><?= formatoPesos($totalVentasHoy) ?></div>
                <div class="sub">pesos</div>
            </div>
        </div>

        <div class="dos-columnas">
            <div class="panel">
                <div class="panel-header">
                    <h2>Movimientos recientes</h2>
                </div>
                <?php if (empty($movimientos)): ?>
                    <p class="sin-datos">No hay movimientos registrados.</p>
                <?php else: ?>
                    <?php foreach ($movimientos as $m): ?>
                        <p style="margin-bottom:10px;">
                            Venta — <?= h($m['nombreProducto']) ?>
                            <span style="float:right; color:#ffb3b3;">-<?= (int)$m['cantidad'] ?></span>
                        </p>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <h2>Alertas de stock bajo</h2>
                </div>
                <?php if (empty($alertasLista)): ?>
                    <p class="sin-datos">No hay productos con stock bajo.</p>
                <?php else: ?>
                    <?php foreach ($alertasLista as $a): ?>
                        <div class="alerta-item">
                            <div class="info"><b><?= h($a['nombreProducto']) ?></b></div>
                            <span><?= (int)$a['stockActual'] ?> und</span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php require 'includes/footer.php'; ?>
</body>
</html>
