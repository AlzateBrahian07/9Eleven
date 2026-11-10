<?php
require_once 'config.php';
requerirLogin();

$paginaActual = 'reportes.php';
$vista = $_GET['vista'] ?? 'lista';

// Reporte activo (pestañas)
$reporte = $_GET['reporte'] ?? 'vendidos';
if (!in_array($reporte, ['vendidos', 'stockbajo', 'valor', 'movimientos'], true)) {
    $reporte = 'vendidos';
}

// ----------------------------------------------------
// KPI: Venta del mes actual
// ----------------------------------------------------
$ventaMes = $pdo->query(
    "SELECT COALESCE(SUM(totalVenta), 0) FROM ventas
     WHERE MONTH(fechaVenta) = MONTH(CURDATE()) AND YEAR(fechaVenta) = YEAR(CURDATE())"
)->fetchColumn();

// ----------------------------------------------------
// KPI: Costo de lo vendido en el mes
// ----------------------------------------------------
$costoMes = $pdo->query(
    "SELECT COALESCE(SUM(dv.cantidad * p.precioCompra), 0)
     FROM detalle_ventas dv
     JOIN ventas v ON v.ID_Venta = dv.ID_Venta
     JOIN productos p ON p.ID_Producto = dv.ID_Producto
     WHERE MONTH(v.fechaVenta) = MONTH(CURDATE()) AND YEAR(v.fechaVenta) = YEAR(CURDATE())"
)->fetchColumn();

// ----------------------------------------------------
// Informe 1: Productos más vendidos (con filtro)
// ----------------------------------------------------
$filtroProducto = '';
$filtroCategoria = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_filtrar'])) {
    $filtroProducto = trim($_POST['producto'] ?? '');
    $filtroCategoria = trim($_POST['categoria'] ?? '');
    $vista = 'lista';
    $reporte = 'vendidos';
}

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

// ----------------------------------------------------
// Informe 2: Stock bajo (stockActual <= stockMinimo)
// ----------------------------------------------------
$stockBajo = $pdo->query(
    "SELECT p.nombreProducto, c.nombreCategoria, p.stockActual, p.stockMinimo
     FROM productos p
     LEFT JOIN categorias c ON c.ID_Categoria = p.ID_Categoria
     WHERE p.stockActual <= p.stockMinimo
     ORDER BY (p.stockMinimo - p.stockActual) DESC"
)->fetchAll();

// ----------------------------------------------------
// Informe 3: Valor total del inventario SUM(stockActual * precioCompra)
// ----------------------------------------------------
$valorPorCategoria = $pdo->query(
    "SELECT COALESCE(c.nombreCategoria, 'Sin categoría') AS categoria,
            COUNT(p.ID_Producto) AS productos,
            COALESCE(SUM(p.stockActual), 0) AS unidades,
            COALESCE(SUM(p.stockActual * p.precioCompra), 0) AS valor
     FROM productos p
     LEFT JOIN categorias c ON c.ID_Categoria = p.ID_Categoria
     GROUP BY c.ID_Categoria, c.nombreCategoria
     ORDER BY valor DESC"
)->fetchAll();
$valorTotal = array_sum(array_column($valorPorCategoria, 'valor'));

// ----------------------------------------------------
// Informe 4: Movimientos de inventario (ventas) con filtro por fechas
// ----------------------------------------------------
$desde = $_GET['desde'] ?? '';
$hasta = $_GET['hasta'] ?? '';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) $desde = '';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) $hasta = '';

$sqlMov = "SELECT v.fechaVenta AS fecha, 'Venta' AS tipo, p.nombreProducto,
                  dv.cantidad, dv.subtotal
           FROM detalle_ventas dv
           JOIN ventas v ON v.ID_Venta = dv.ID_Venta
           JOIN productos p ON p.ID_Producto = dv.ID_Producto
           WHERE 1=1";
$paramsMov = [];
if ($desde !== '') {
    $sqlMov .= " AND DATE(v.fechaVenta) >= ?";
    $paramsMov[] = $desde;
}
if ($hasta !== '') {
    $sqlMov .= " AND DATE(v.fechaVenta) <= ?";
    $paramsMov[] = $hasta;
}
$sqlMov .= " ORDER BY v.fechaVenta DESC LIMIT 200";

$stmtMov = $pdo->prepare($sqlMov);
$stmtMov->execute($paramsMov);
$movimientos = $stmtMov->fetchAll();

// ----------------------------------------------------
// Opcional: exportar a CSV (debe ir antes de imprimir HTML)
// ----------------------------------------------------
if (($_GET['exportar'] ?? '') === 'csv' && $reporte !== 'vendidos') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="reporte_' . $reporte . '.csv"');
    echo "\xEF\xBB\xBF"; // BOM para que Excel lea bien las tildes
    $out = fopen('php://output', 'w');

    if ($reporte === 'stockbajo') {
        fputcsv($out, ['Producto', 'Categoria', 'Stock actual', 'Stock minimo'], ';');
        foreach ($stockBajo as $r) {
            fputcsv($out, [$r['nombreProducto'], $r['nombreCategoria'] ?? 'Sin categoria', $r['stockActual'], $r['stockMinimo']], ';');
        }
    } elseif ($reporte === 'valor') {
        fputcsv($out, ['Categoria', 'Productos', 'Unidades', 'Valor'], ';');
        foreach ($valorPorCategoria as $r) {
            fputcsv($out, [$r['categoria'], $r['productos'], $r['unidades'], $r['valor']], ';');
        }
    } else {
        fputcsv($out, ['Fecha', 'Tipo', 'Producto', 'Cantidad', 'Valor'], ';');
        foreach ($movimientos as $r) {
            fputcsv($out, [$r['fecha'], $r['tipo'], $r['nombreProducto'], $r['cantidad'], $r['subtotal']], ';');
        }
    }
    fclose($out);
    exit;
}

// Helper para armar la URL de exportar conservando el filtro de fechas
$queryExport = http_build_query(array_filter([
    'reporte'  => $reporte,
    'desde'    => $desde,
    'hasta'    => $hasta,
    'exportar' => 'csv',
]));
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
                <div class="etiqueta">COSTO VENDIDO MES</div>
                <div class="valor"><?= number_format($costoMes / 1000000, 1) ?> M</div>
                <div class="sub">pesos colombianos</div>
            </div>
        </div>

        <!-- Pestañas de reportes -->
        <div class="barra-acciones">
            <a href="reportes.php?reporte=vendidos"    class="btn <?= $reporte === 'vendidos' ? 'btn-rojo' : '' ?>">Más vendidos</a>
            <a href="reportes.php?reporte=stockbajo"   class="btn <?= $reporte === 'stockbajo' ? 'btn-rojo' : '' ?>">Stock bajo</a>
            <a href="reportes.php?reporte=valor"       class="btn <?= $reporte === 'valor' ? 'btn-rojo' : '' ?>">Valor del inventario</a>
            <a href="reportes.php?reporte=movimientos" class="btn <?= $reporte === 'movimientos' ? 'btn-rojo' : '' ?>">Movimientos</a>
        </div>

        <?php if ($reporte === 'vendidos'): ?>
        <div class="panel">
            <div class="panel-header">
                <h2>Productos más vendidos</h2>
                <a href="reportes.php?reporte=vendidos&vista=filtrar" class="btn btn-rojo">Filtrar reporte</a>
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

        <?php elseif ($reporte === 'stockbajo'): ?>
        <div class="panel">
            <div class="panel-header">
                <h2>Productos con stock bajo</h2>
                <a href="reportes.php?<?= h($queryExport) ?>" class="btn btn-rojo">Exportar CSV</a>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Stock actual</th>
                        <th>Stock mínimo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($stockBajo)): ?>
                        <tr><td colspan="4" class="sin-datos">No hay productos con stock bajo.</td></tr>
                    <?php else: ?>
                        <?php foreach ($stockBajo as $p): ?>
                            <tr>
                                <td><?= h($p['nombreProducto']) ?></td>
                                <td><?= h($p['nombreCategoria'] ?? 'Sin categoría') ?></td>
                                <td><?= (int)$p['stockActual'] ?></td>
                                <td><?= (int)$p['stockMinimo'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php elseif ($reporte === 'valor'): ?>
        <div class="panel">
            <div class="panel-header">
                <h2>Valor total del inventario: <?= formatoPesos($valorTotal) ?></h2>
                <a href="reportes.php?<?= h($queryExport) ?>" class="btn btn-rojo">Exportar CSV</a>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Categoría</th>
                        <th>Productos</th>
                        <th>Unidades</th>
                        <th>Valor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($valorPorCategoria)): ?>
                        <tr><td colspan="4" class="sin-datos">No hay productos registrados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($valorPorCategoria as $r): ?>
                            <tr>
                                <td><?= h($r['categoria']) ?></td>
                                <td><?= (int)$r['productos'] ?></td>
                                <td><?= (int)$r['unidades'] ?></td>
                                <td><?= formatoPesos($r['valor']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php else: ?>
        <div class="panel">
            <div class="panel-header">
                <h2>Movimientos de inventario</h2>
                <a href="reportes.php?<?= h($queryExport) ?>" class="btn btn-rojo">Exportar CSV</a>
            </div>

            <div class="form-box" style="margin:15px auto;">
                <form method="GET" action="reportes.php">
                    <input type="hidden" name="reporte" value="movimientos">
                    <div class="form-grupo">
                        <label for="desde">Desde</label>
                        <input type="date" id="desde" name="desde" value="<?= h($desde) ?>">
                    </div>
                    <div class="form-grupo">
                        <label for="hasta">Hasta</label>
                        <input type="date" id="hasta" name="hasta" value="<?= h($hasta) ?>">
                    </div>
                    <div class="acciones-form">
                        <a href="reportes.php?reporte=movimientos" class="btn">Limpiar</a>
                        <button type="submit" class="btn btn-rojo">Filtrar</button>
                    </div>
                </form>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Valor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($movimientos)): ?>
                        <tr><td colspan="5" class="sin-datos">No hay movimientos en ese rango.</td></tr>
                    <?php else: ?>
                        <?php foreach ($movimientos as $m): ?>
                            <tr>
                                <td><?= h($m['fecha']) ?></td>
                                <td><?= h($m['tipo']) ?></td>
                                <td><?= h($m['nombreProducto']) ?></td>
                                <td><?= (int)$m['cantidad'] ?></td>
                                <td><?= formatoPesos($m['subtotal']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </main>
</div>

<?php require 'includes/footer.php'; ?>
</body>
</html>
