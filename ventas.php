<?php
require_once 'config.php';
requerirLogin();

$paginaActual = 'ventas.php';

// Fecha consultada: hoy por defecto, pero permite navegar a otra fecha vía GET
$fecha = $_GET['fecha'] ?? date('Y-m-d');

// Encabezados de venta del día + cuántos productos distintos llevó cada venta
$stmt = $pdo->prepare(
    "SELECT v.ID_Venta, v.fechaVenta, v.totalVenta,
            COUNT(dv.ID_Detalle) AS totalItems
     FROM ventas v
     LEFT JOIN detalle_ventas dv ON dv.ID_Venta = v.ID_Venta
     WHERE v.fechaVenta = ?
     GROUP BY v.ID_Venta, v.fechaVenta, v.totalVenta
     ORDER BY v.ID_Venta DESC"
);
$stmt->execute([$fecha]);
$ventas = $stmt->fetchAll();

// Total vendido en el día
$totalDia = array_sum(array_column($ventas, 'totalVenta'));

// Detalle de productos vendidos en el día (para mostrar debajo de la tabla principal)
$stmtDetalle = $pdo->prepare(
    "SELECT p.nombreProducto, dv.cantidad, dv.precioUnitario, dv.subtotal, v.ID_Venta
     FROM detalle_ventas dv
     JOIN ventas v ON v.ID_Venta = dv.ID_Venta
     JOIN productos p ON p.ID_Producto = dv.ID_Producto
     WHERE v.fechaVenta = ?
     ORDER BY v.ID_Venta DESC"
);
$stmtDetalle->execute([$fecha]);
$detalles = $stmtDetalle->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>9Eleven - Ventas</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php require 'includes/header.php'; ?>

<div class="contenedor-principal">
    <?php require 'includes/sidebar.php'; ?>

    <main class="contenido">
        <h1>Ventas</h1>

        <div class="panel">
            <div class="panel-header">
                <h2>Ventas del día — <?= h($fecha) ?></h2>
                <form method="GET" action="ventas.php" style="display:flex; gap:8px; align-items:center;">
                    <input type="date" name="fecha" value="<?= h($fecha) ?>"
                           style="padding:6px 10px; border-radius:4px; border:none; background-color:var(--azul-hielo); color:#1a1a1a;">
                    <button type="submit" class="btn">Consultar</button>
                </form>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>ID Venta</th>
                        <th>Fecha</th>
                        <th>Items</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ventas)): ?>
                        <tr><td colspan="4" class="sin-datos">No hay ventas registradas para esta fecha.</td></tr>
                    <?php else: ?>
                        <?php foreach ($ventas as $v): ?>
                            <tr>
                                <td>V<?= str_pad($v['ID_Venta'], 4, '0', STR_PAD_LEFT) ?></td>
                                <td><?= h($v['fechaVenta']) ?></td>
                                <td><?= (int)$v['totalItems'] ?></td>
                                <td><?= formatoPesos($v['totalVenta']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($ventas)): ?>
                <tfoot>
                    <tr>
                        <td colspan="3" style="text-align:right; font-weight:bold;">Total del día:</td>
                        <td style="font-weight:bold;"><?= formatoPesos($totalDia) ?></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>

        <?php if (!empty($detalles)): ?>
        <div class="panel" style="margin-top:20px;">
            <div class="panel-header">
                <h2>Detalle de productos vendidos</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>ID Venta</th>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Precio Unit.</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detalles as $d): ?>
                        <tr>
                            <td>V<?= str_pad($d['ID_Venta'], 4, '0', STR_PAD_LEFT) ?></td>
                            <td><?= h($d['nombreProducto']) ?></td>
                            <td><?= (int)$d['cantidad'] ?></td>
                            <td><?= formatoPesos($d['precioUnitario']) ?></td>
                            <td><?= formatoPesos($d['subtotal']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </main>
</div>

<?php require 'includes/footer.php'; ?>
</body>
</html>
