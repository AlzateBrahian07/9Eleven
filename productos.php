<?php
require_once 'config.php';
requerirLogin();

$paginaActual = 'productos.php';
$error = '';
$exito = '';

// Vista activa: lista | nuevo | consultar | eliminar
$vista = $_GET['vista'] ?? 'lista';

// Categorías para el select del formulario "Nuevo producto"
$categorias = $pdo->query("SELECT * FROM categorias ORDER BY nombreCategoria")->fetchAll();

// ----------------------------------------------------
// ACCIÓN: Agregar producto
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_nuevo'])) {
    $codigo       = trim($_POST['codigo'] ?? '');
    $nombre       = trim($_POST['nombre'] ?? '');
    $idCategoria  = $_POST['categoria'] ?: null;
    $precioCompra = (float)($_POST['precio_compra'] ?? 0);
    $precioVenta  = (float)($_POST['precio_venta'] ?? 0);
    $iva          = (float)($_POST['iva'] ?? 0);
    $stockActual  = (int)($_POST['stock_actual'] ?? 0);
    $stockMinimo  = (int)($_POST['stock_minimo'] ?? 0);

    if ($codigo === '' || $nombre === '') {
        $error = 'Código y nombre del producto son obligatorios.';
        $vista = 'nuevo';
    } else {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO productos
                 (codigoProducto, nombreProducto, precioCompra, precioVenta, iva, stockActual, stockMinimo, ID_Categoria)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$codigo, $nombre, $precioCompra, $precioVenta, $iva, $stockActual, $stockMinimo, $idCategoria]);
            $exito = "Producto \"$nombre\" agregado correctamente.";
            $vista = 'lista';
        } catch (PDOException $e) {
            $error = ($e->getCode() === '23000')
                ? 'Ya existe un producto con ese código.'
                : 'Error al guardar el producto.';
            $vista = 'nuevo';
        }
    }
}

// ----------------------------------------------------
// ACCIÓN: Eliminar producto
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_eliminar'])) {
    $nombreBuscar = trim($_POST['nombre_eliminar'] ?? '');

    if ($nombreBuscar === '') {
        $error = 'Escribe el nombre del producto a eliminar.';
        $vista = 'eliminar';
    } else {
        $stmt = $pdo->prepare("DELETE FROM productos WHERE nombreProducto = ?");
        $stmt->execute([$nombreBuscar]);

        if ($stmt->rowCount() > 0) {
            $exito = "Producto \"$nombreBuscar\" eliminado correctamente.";
            $vista = 'lista';
        } else {
            $error = "No se encontró un producto llamado \"$nombreBuscar\".";
            $vista = 'eliminar';
        }
    }
}

// ----------------------------------------------------
// ACCIÓN: Consultar producto (filtro de búsqueda)
// ----------------------------------------------------
$filtroNombre = '';
$filtroCategoria = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_consultar'])) {
    $filtroNombre = trim($_POST['nombre_consultar'] ?? '');
    $filtroCategoria = trim($_POST['categoria_consultar'] ?? '');
    $vista = 'lista';
}

// ----------------------------------------------------
// Listado de productos (aplica filtro si viene de "Consultar")
// ----------------------------------------------------
$sql = "SELECT p.*, c.nombreCategoria
        FROM productos p
        LEFT JOIN categorias c ON c.ID_Categoria = p.ID_Categoria
        WHERE 1=1";
$params = [];

if ($filtroNombre !== '') {
    $sql .= " AND p.nombreProducto LIKE ?";
    $params[] = "%$filtroNombre%";
}
if ($filtroCategoria !== '') {
    $sql .= " AND c.nombreCategoria LIKE ?";
    $params[] = "%$filtroCategoria%";
}
$sql .= " ORDER BY p.codigoProducto";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$productos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>9Eleven - Productos</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php require 'includes/header.php'; ?>

<div class="contenedor-principal">
    <?php require 'includes/sidebar.php'; ?>

    <main class="contenido">
        <h1>Productos</h1>

        <?php if ($error): ?><div class="mensaje-error"><?= h($error) ?></div><?php endif; ?>
        <?php if ($exito): ?><div class="mensaje-ok"><?= h($exito) ?></div><?php endif; ?>

        <h2 style="text-align:center; margin-bottom:14px; font-size:1.2rem;">Lista de Productos</h2>

        <div class="barra-acciones">
            <a href="productos.php?vista=nuevo" class="btn">+ Nuevo Producto</a>
            <a href="productos.php?vista=eliminar" class="btn btn-rojo">X Eliminar Producto</a>
            <a href="productos.php?vista=consultar" class="btn">= Consultar Producto</a>
        </div>

        <?php if ($vista === 'nuevo'): ?>
            <div class="form-box">
                <h2>Información general</h2>
                <form method="POST" action="productos.php">
                    <div class="form-grupo">
                        <label for="nombre">Nombre producto</label>
                        <input type="text" id="nombre" name="nombre" required>
                    </div>
                    <div class="form-grupo">
                        <label for="codigo">Código</label>
                        <input type="text" id="codigo" name="codigo" required>
                    </div>
                    <div class="form-grupo">
                        <label for="categoria">Categoría</label>
                        <select id="categoria" name="categoria">
                            <option value="">-- Seleccionar --</option>
                            <?php foreach ($categorias as $c): ?>
                                <option value="<?= (int)$c['ID_Categoria'] ?>">
                                    <?= h($c['nombreCategoria']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <h2 style="margin-top:18px;">Información comercial</h2>
                    <div class="form-grupo">
                        <label for="precio_compra">Precio de compra</label>
                        <input type="number" step="0.01" id="precio_compra" name="precio_compra" required>
                    </div>
                    <div class="form-grupo">
                        <label for="precio_venta">Precio de venta</label>
                        <input type="number" step="0.01" id="precio_venta" name="precio_venta" required>
                    </div>
                    <div class="form-grupo">
                        <label for="iva">IVA (%)</label>
                        <input type="number" step="0.01" id="iva" name="iva" value="0">
                    </div>
                    <div class="form-grupo">
                        <label for="stock_actual">Stock actual</label>
                        <input type="number" id="stock_actual" name="stock_actual" value="0" required>
                    </div>
                    <div class="form-grupo">
                        <label for="stock_minimo">Stock mínimo</label>
                        <input type="number" id="stock_minimo" name="stock_minimo" value="0" required>
                    </div>

                    <div class="acciones-form">
                        <a href="productos.php" class="btn">Cancelar</a>
                        <button type="submit" name="accion_nuevo" class="btn btn-rojo">AGREGAR PRODUCTO</button>
                    </div>
                </form>
            </div>

        <?php elseif ($vista === 'consultar'): ?>
            <div class="form-box">
                <h2>Consultar producto</h2>
                <form method="POST" action="productos.php">
                    <div class="form-grupo">
                        <label for="nombre_consultar">Nombre producto</label>
                        <input type="text" id="nombre_consultar" name="nombre_consultar">
                    </div>
                    <div class="form-grupo">
                        <label for="categoria_consultar">Categoría</label>
                        <input type="text" id="categoria_consultar" name="categoria_consultar">
                    </div>
                    <div class="acciones-form">
                        <a href="productos.php" class="btn">Cancelar</a>
                        <button type="submit" name="accion_consultar" class="btn btn-rojo">Mostrar en lista</button>
                    </div>
                </form>
            </div>

        <?php elseif ($vista === 'eliminar'): ?>
            <div class="form-box">
                <h2>Eliminar producto</h2>
                <form method="POST" action="productos.php" onsubmit="return confirm('¿Seguro que deseas eliminar este producto?');">
                    <div class="form-grupo">
                        <label for="nombre_eliminar">Nombre producto</label>
                        <input type="text" id="nombre_eliminar" name="nombre_eliminar" required>
                    </div>
                    <div class="acciones-form">
                        <a href="productos.php" class="btn">Cancelar</a>
                        <button type="submit" name="accion_eliminar" class="btn btn-rojo">Eliminar</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Categoría</th>
                    <th>Precio</th>
                    <th>Stock</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($productos)): ?>
                    <tr><td colspan="6" class="sin-datos">No se encontraron productos.</td></tr>
                <?php else: ?>
                    <?php foreach ($productos as $p):
                        $bajo = $p['stockActual'] <= $p['stockMinimo'];
                    ?>
                        <tr>
                            <td><?= h($p['codigoProducto']) ?></td>
                            <td><?= h($p['nombreProducto']) ?></td>
                            <td><?= h($p['nombreCategoria'] ?? '—') ?></td>
                            <td><?= formatoPesos($p['precioVenta']) ?></td>
                            <td><?= (int)$p['stockActual'] ?></td>
                            <td>
                                <span class="estado <?= $bajo ? 'estado-bajo' : 'estado-ok' ?>">
                                    <?= $bajo ? 'Bajo' : 'OK' ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </main>
</div>

<?php require 'includes/footer.php'; ?>
</body>
</html>
