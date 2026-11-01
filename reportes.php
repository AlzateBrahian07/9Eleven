<?php
require_once 'config.php';
requerirLogin();

$paginaActual = 'reportes.php';
$vista = $_GET['vista'] ?? 'lista';

// ----------------------------------------------------
// KPI: Venta del mes actual (suma de totalVenta)
// ----------------------------------------------------
$ventaMes = $pdo->query(
    "SELECT COALESCE(SUM(totalVenta), 0) FROM ventas
     WHERE MONTH(fechaVenta) = MONTH(CURDATE()) AND YEAR(fechaVenta) = YEAR(CURDATE())"
)->fetchColumn();

// ----------------------------------------------------
// KPI: Costo de lo vendido en el mes (precioCompra * cantidad)
// Nota: la BD no tiene tabla de compras a proveedor, así que esto
// representa el costo de los productos vendidos, no compras reales.
// ----------------------------------------------------
$costoMes = $pdo->query(
    "SELECT COALESCE(SUM(dv.cantidad * p.precioCompra), 0)
     FROM detalle_ventas dv
     JOIN ventas v ON v.ID_Venta = dv.ID_Venta
     JOIN productos p ON p.ID_Producto = dv.ID_Producto
     WHERE MONTH(v.fechaVenta) = MONTH(CURDATE()) AND YEAR(v.fechaVenta) = YEAR(CURDATE())"
)->fetchColumn();

// ----------------------------------------------------
// Filtro de búsqueda sobre "productos más vendidos"
// ----------------------------------------------------
$filtroProducto = '';
$filtroCategoria = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_filtrar'])) {
    $filtroProducto = trim($_POST['producto'] ?? '');
    $filtroCategoria = trim($_POST['categoria'] ?? '');
    $vista = 'lista';
}

// ----------------------------------------------------
// Productos más vendidos (histórico, agrupado por producto)
// ----------------------------------------------------
$sql = "SELECT p.nombreProducto, c.nombreCategoria,
               SUM(dv.cantidad) AS unidades,
               SUM(dv.subtotal) AS ingresos
        FROM detalle_ventas dv
        JOIN productos p ON p.ID_Producto = dv.ID_Producto
        LEFT JOIN categorias c ON c.ID_Categoria = p.ID_Categoria
        WHERE 1=1";
$params = [];

if ($filtroProducto !== '') {
    $sql .= " AND p.nombreProducto LIKE ?";
    $params[] = "%$filtroProducto%";
}
if ($filtroCategoria !== '') {
    $sql .= " AND c.nombreCategoria LIKE ?";
    $params[] = "%$filtroCategoria%";
}
$sql .= " GROUP BY p.ID_Producto, p.nombreProducto, c.nombreCategoria
          ORDER BY unidades DESC
          LIMIT 20";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$productosMasVendidos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>9Eleven - Reportes</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php require 'includes/header.php'; ?>

<div class="contenedor-principal">
    <?php require 'includes/sidebar.php'; ?>

    <main class="contenido">
        <h1>Reportes</h1>

        <div class="tarjetas">
            <div class="tarjeta">
                <div class="etiqueta">VENTA MES</div>
                <div class="valor"><?= number_format($ventaMes / 1000000, 1) ?> M</div>
                <div class="sub">pesos colombianos</div>
            </div>
            <div class="tarjeta">
                <div class="etiqueta">VENTA MES</div>
                <div class="valor"><?= number_format($ventaMes / 1000000, 1) ?> M</div>
                <div class="sub">pesos colombianos</div>
            </div>
            <div class="tarjeta">
                <div class="etiqueta">COSTO VENDIDO MES</div>
                <div class="valor"><?= number_format($costoMes / 1000000, 1) ?> M</div>
                <div class="sub">pesos colombianos</div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <h2>Productos más vendidos</h2>
                <a href="reportes.php?vista=filtrar" class="btn btn-rojo">Filtrar reporte</a>
            </div>

            <?php if ($vista === 'filtrar'): ?>
                <div class="form-box" style="margin:15px auto;">
                    <h2>Filtrar productos más vendidos</h2>
                    <form method="POST" action="reportes.php">
                        <div class="form-grupo">
                            <label for="producto">Producto</label>
                            <input type="text" id="producto" name="producto">
                        </div>
                        <div class="form-grupo">
                            <label for="categoria">Categoría</label>
                            <input type="text" id="categoria" name="categoria">
                        </div>
                        <div class="acciones-form">
                            <a href="reportes.php" class="btn">Cancelar</a>
                            <button type="submit" name="accion_filtrar" class="btn btn-rojo">Filtrar</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <table>
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Unidades</th>
                        <th>Ingresos</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($productosMasVendidos)): ?>
                        <tr><td colspan="4" class="sin-datos">No hay ventas registradas todavía.</td></tr>
                    <?php else: ?>
                        <?php foreach ($productosMasVendidos as $p): ?>
                            <tr>
                                <td><?= h($p['nombreProducto']) ?></td>
                                <td><?= h($p['nombreCategoria'] ?? '—') ?></td>
                                <td><?= (int)$p['unidades'] ?></td>
                                <td><?= formatoPesos($p['ingresos']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<?php require 'includes/footer.php'; ?>
</body>
</html>
