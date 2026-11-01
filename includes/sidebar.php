<?php
// $paginaActual debe estar definida en la página que incluye este sidebar
$paginaActual = $paginaActual ?? '';

$items = [
    'dashboard.php'    => 'Dashboard',
    'productos.php'    => 'Productos',
    'categorias.php'   => 'Categorias',
    'proveedores.php'  => 'Proveedores',
    'ventas.php'       => 'Ventas',
    'reportes.php'     => 'Reportes',
    'alertaStock.php'  => 'Alertas stock',
];
?>
<aside class="sidebar">
    <div class="titulo-rol">Administrador</div>
    <nav>
        <?php foreach ($items as $archivo => $etiqueta): ?>
            <a href="<?= $archivo ?>" class="<?= $paginaActual === $archivo ? 'activo' : '' ?>">
                <?= h($etiqueta) ?>
            </a>
        <?php endforeach; ?>
    </nav>
</aside>
