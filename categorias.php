<?php
require_once 'config.php';
requerirLogin();

$paginaActual = 'categorias.php';
$error = '';
$exito = '';
$vista = $_GET['vista'] ?? 'lista';

// ----------------------------------------------------
// ACCIÓN: Nueva categoría
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_nueva']))
{
    $nombre = trim($_POST['nombre'] ?? '');

    if ($nombre === '')
    {
        $error = 'El nombre de la categoría es obligatorio.';
        $vista = 'nueva';
    } else
    {
        $stmtcheck = $pdo->prepare("SELECT COUNT(*) FROM categorias WHERE nombreCategoria = ?");
        $stmtcheck->execute([$nombre]);

        if ($stmtcheck->fetchColumn() > 0)
        {
            $error = "Ya existe esta categoria con el nombre \"$nombre\".";
            $vista = 'nueva';
        } else
        {
            $stmt = $pdo->prepare("INSERT INTO categorias (nombreCategoria) VALUES (?)");
            $stmt->execute([$nombre]);
            $exito = "Categoria \"$nombre\" creada correctamente";
            $vista = 'lista';
        }
    }
}

//-----------------------------------------------------
//ACCIÓN: Editar Categoria
//-----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_editar']))
{
    $nombreActual = trim($_POST['nombre_actual'] ?? '');
    $nombreNuevo = trim($_POST['nombre_nuevo'] ?? '');

    if ($nombreActual === '' || $nombreNuevo === '')
    {
        $error = "Ambos campos son obligatorios";
        $vista = 'editar';
    } else
    {
        // Validar que el nuevo nombre no exista en otra categoria
        $stmtcheck = $pdo->prepare("SELECT COUNT(*) FROM categorias WHERE nombreCategoria = ?");
        $stmtcheck->execute([$nombreNuevo]);

        if ($stmtcheck->fetchColumn() > 0)
        {
            $error = "Ya existe una categoria con el nombre \"$nombreNuevo\".";
            $vista = 'editar';
        } else
        {
            // Actualiza el nombre
            $stmt = $pdo->prepare("UPDATE categorias SET nombreCategoria = ? WHERE nombreCategoria = ?");
            $stmt->execute([$nombreNuevo, $nombreActual]);

            if ($stmt->rowCount() > 0)
            {
                $exito = "Categoria \"$nombreActual\" renombrada a \"$nombreNuevo\" correctamente.";
                $vista = 'lista';
            } else
            {
                $error = "No se encontro una categoria llamada \"$nombreActual\".";
                $vista = 'editar';
            }
        }
    }
}

// ----------------------------------------------------
// ACCIÓN: Eliminar categoría
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_eliminar'])) {
    $nombreBuscar = trim($_POST['nombre_eliminar'] ?? '');

    if ($nombreBuscar === '') {
        $error = 'Escribe el nombre de la categoría a eliminar.';
        $vista = 'eliminar';
    } else {
        $stmt = $pdo->prepare("DELETE FROM categorias WHERE nombreCategoria = ?");
        $stmt->execute([$nombreBuscar]);

        if ($stmt->rowCount() > 0) {
            $exito = "Categoría \"$nombreBuscar\" eliminada correctamente.";
            $vista = 'lista';
        } else {
            $error = "No se encontró una categoría llamada \"$nombreBuscar\".";
            $vista = 'eliminar';
        }
    }
}

// ----------------------------------------------------
// ACCIÓN: Consultar (filtro de búsqueda)
// ----------------------------------------------------
$filtroNombre = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_consultar'])) {
    $filtroNombre = trim($_POST['nombre_consultar'] ?? '');
    $vista = 'lista';
}

// ----------------------------------------------------
// Listado de categorías + cantidad de productos por categoría
// ----------------------------------------------------
$sql = "SELECT c.ID_Categoria, c.nombreCategoria, COUNT(p.ID_Producto) AS totalProductos
        FROM categorias c
        LEFT JOIN productos p ON p.ID_Categoria = c.ID_Categoria
        WHERE 1=1";
$params = [];

if ($filtroNombre !== '') {
    $sql .= " AND c.nombreCategoria LIKE ?";
    $params[] = "%$filtroNombre%";
}
$sql .= " GROUP BY c.ID_Categoria, c.nombreCategoria ORDER BY c.nombreCategoria";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$categorias = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>9Eleven - Categorías</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php require 'includes/header.php'; ?>

<div class="contenedor-principal">
    <?php require 'includes/sidebar.php'; ?>

    <main class="contenido">
        <h1>Categorias</h1>

        <?php if ($error): ?><div class="mensaje-error"><?= h($error) ?></div><?php endif; ?>
        <?php if ($exito): ?><div class="mensaje-ok"><?= h($exito) ?></div><?php endif; ?>

        <div class="panel">
            <div class="panel-header">
                <h2>Lista de Categorías</h2>
                <div class="barra-acciones" style="margin-bottom:0;">
                    <a href="categorias.php?vista=consultar" class="btn btn-rojo">Buscar categoria</a>
                    <a href="categorias.php?vista=editar" class="btn btn-rojo">Editar categoria</a>
                    <a href="categorias.php?vista=eliminar" class="btn btn-rojo">Eliminar categoria</a>
                    <a href="categorias.php?vista=nueva" class="btn btn-rojo">+ Nueva categoria</a>
                </div>
            </div>

            <?php if ($vista === 'nueva'): ?>
                <div class="form-box" style="margin:15px auto;">
                    <h2>Nueva categoría</h2>
                    <form method="POST" action="categorias.php">
                        <div class="form-grupo">
                            <label for="nombre">Nombre</label>
                            <input type="text" id="nombre" name="nombre" placeholder="Ej: Bebidas, Snacks, Lácteos..." required>
                        </div>
                        <div class="acciones-form">
                            <a href="categorias.php" class="btn">Cancelar</a>
                            <button type="submit" name="accion_nueva" class="btn btn-rojo">Guardar categoría</button>
                        </div>
                    </form>
                </div>


                <?php elseif ($vista === 'editar'): ?>
                <div class="form-box" style="margin:15px auto;">
                    <h2>Editar categoría</h2>
                    <form method="POST" action="categorias.php">
                        <div class="form-grupo">
                            <label for="nombre_actual">Nombre actual</label>
                            <input type="text" id="nombre_actual" name="nombre_actual" required>
                        </div>
                        <div class="form-grupo">
                            <label for="nombre_nuevo">Nuevo nombre</label>
                            <input type="text" id="nombre_nuevo" name="nombre_nuevo" required>
                        </div>
                        <div class="acciones-form">
                            <a href="categorias.php" class="btn">Cancelar</a>
                            <button type="submit" name="accion_editar" class="btn btn-rojo">Guardar cambios</button>
                        </div>
                    </form>
                </div>
                
            <?php elseif ($vista === 'consultar'): ?>
                <div class="form-box" style="margin:15px auto;">
                    <h2>Consultar categoría</h2>
                    <form method="POST" action="categorias.php">
                        <div class="form-grupo">
                            <label for="nombre_consultar">Nombre categoría</label>
                            <input type="text" id="nombre_consultar" name="nombre_consultar">
                        </div>
                        <div class="acciones-form">
                            <a href="categorias.php" class="btn">Cancelar</a>
                            <button type="submit" name="accion_consultar" class="btn btn-rojo">Mostrar en lista</button>
                        </div>
                    </form>
                </div>

            <?php elseif ($vista === 'eliminar'): ?>
                <div class="form-box" style="margin:15px auto;">
                    <h2>Eliminar categoría</h2>
                    <form method="POST" action="categorias.php" onsubmit="return confirm('¿Seguro que deseas eliminar esta categoría?');">
                        <div class="form-grupo">
                            <label for="nombre_eliminar">Nombre categoría</label>
                            <input type="text" id="nombre_eliminar" name="nombre_eliminar" required>
                        </div>
                        <div class="acciones-form">
                            <a href="categorias.php" class="btn">Cancelar</a>
                            <button type="submit" name="accion_eliminar" class="btn btn-rojo">Eliminar</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Productos</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categorias)): ?>
                        <tr><td colspan="3" class="sin-datos">No se encontraron categorías.</td></tr>
                    <?php else: ?>
                        <?php foreach ($categorias as $c): ?>
                            <tr>
                                <td>C<?= str_pad($c['ID_Categoria'], 3, '0', STR_PAD_LEFT) ?></td>
                                <td><?= h($c['nombreCategoria']) ?></td>
                                <td><?= (int)$c['totalProductos'] ?></td>
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
