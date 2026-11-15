<?php
require_once 'config.php';
requerirLogin();

$paginaActual = 'entradas.php';

$error = '';
$entradas = [];

// Últimas 30 entradas con su proveedor y cuántos productos distintos trajo cada una
try {
    $stmt = $pdo->query(
        "SELECT e.ID_Entrada, e.fechaEntrada, e.totalEntrada, pr.nombreEmpresa,
                COUNT(de.ID_Detalle) AS totalItems
         FROM entradas e
         LEFT JOIN proveedores pr ON pr.ID_Proveedor = e.ID_Proveedor
         LEFT JOIN detalle_entradas de ON de.ID_Entrada = e.ID_Entrada
         GROUP BY e.ID_Entrada, e.fechaEntrada, e.totalEntrada, pr.nombreEmpresa
         ORDER BY e.fechaEntrada DESC, e.ID_Entrada DESC
         LIMIT 30"
    );
    $entradas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('9Eleven entradas: ' . $e->getMessage());
    $error = 'No se pudieron leer las entradas. Ejecuta migraciones/002_entradas.sql en phpMyAdmin.';
}
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
        <p>        <?php if ($error): ?>
            <div class="mensaje-error"><?= h($error) ?></div>
        <?php endif; ?>

        <div class="panel">
            <div class="panel-header">
                <h2>Últimas entradas</h2>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>ID Entrada</th>
                        <th>Fecha</th>
                        <th>Proveedor</th>
                        <th>Items</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($entradas)): ?>
                        <tr><td colspan="5" class="sin-datos">No hay entradas registradas.</td></tr>
                    <?php else: ?>
                        <?php foreach ($entradas as $e): ?>
                            <tr>
                                <td>E<?= str_pad((string)$e['ID_Entrada'], 4, '0', STR_PAD_LEFT) ?></td>
                                <td><?= h($e['fechaEntrada']) ?></td>
                                <td><?= h($e['nombreEmpresa'] ?? '(proveedor eliminado)') ?></td>
                                <td><?= (int)$e['totalItems'] ?></td>
                                <td><?= formatoPesos($e['totalEntrada']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>.</p>
    </main>
</div>

<?php require 'includes/footer.php'; ?>
</body>
</html>