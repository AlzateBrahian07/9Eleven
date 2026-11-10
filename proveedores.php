<?php
require_once 'config.php';
requerirLogin();

$paginaActual = 'proveedores.php';
$error = '';
$exito = '';
$vista = $_GET['vista'] ?? 'lista';

$categorias = $pdo->query("SELECT * FROM categorias ORDER BY nombreCategoria")->fetchAll();

// ----------------------------------------------------
// ACCIÓN: Nuevo proveedor
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_nuevo'])) 
{
    $nombreEmpresa = trim($_POST['nombre_proveedor'] ?? '');
    $contacto      = trim($_POST['contacto'] ?? '');
    $telefono      = trim($_POST['telefono'] ?? '');
    $idCategoria   = $_POST['categoria'] ?: null;

    if ($nombreEmpresa === '') 
    {
        $error = 'El nombre del proveedor es obligatorio.';
        $vista = 'nuevo';
    } elseif ($telefono !== '' && !preg_match('/^[0-9\+\-\(\)\s]{7,20}$/',$telefono)){
        $error = 'El formato del telefono no es valido';
        $vista = 'nuevo';
    }else
    {
        $stmt = $pdo->prepare(
            "INSERT INTO proveedores (nombreEmpresa, contacto, telefono, ID_Categoria) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$nombreEmpresa, $contacto, $telefono, $idCategoria]);
        $exito = "Proveedor \"$nombreEmpresa\" agregado correctamente.";
        $vista = 'lista';
    }
}

//-----------------------------------------------------
// ACCION: Editar proveedor
//-----------------------------------------------------
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_editar']))
    {
        $idProveedor = (int)($_POST['id_proveedor'] ?? 0);
        $nombreEmpresa = trim($_POST['nombre_proveedor']?? '');
        $contacto = trim($_POST['contacto']?? '');
        $telefono = trim($_POST['telefono']?? '');
        $idCategoria = $_POST['categoria'] ?: null;
        if ($nombreEmpresa === '')
        {
            $error = 'el nombre del proveedor es obligatorio';
            $vista = 'editar';
            $_GET['id'] = $idProveedor;
        } elseif($telefono !== '' && !preg_match('/^[0-9\+\-\(\)\s]{7,20}$/', $telefono))
        {
            $error = 'El formato del telefono no es valido';
            $vista = 'editar';
            $_GET['id'] = $idProveedor;
        } else
        {
            $stmt = $pdo->prepare(
                "UPDATE proveedores SET nombreEmpresa = ?, contacto=?, telefono=?, ID_Categoria=? WHERE ID_proveedor=?"
            );
            $stmt->execute([$nombreEmpresa, $contacto, $telefono, $idCategoria, $idProveedor]);
            $exito = "Proveedor \"$nombreEmpresa\" actualizado correctamente.";
            $vista = 'lista';

        }

    }
// ----------------------------------------------------
// ACCIÓN: Eliminar proveedor
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_eliminar'])) {
    $idEliminar = (int)($_POST['id_eliminar'] ?? 0);
    $nombreEliminar = '';

    if (!csrfValido()) {
        $error = 'Token de seguridad inválido. Recarga la página e inténtalo de nuevo.';
    } elseif ($idEliminar <= 0) {
        $error = 'Proveedor no válido.';
    } else {
        try {
            $stmtNombre = $pdo->prepare("SELECT nombreEmpresa FROM proveedores WHERE ID_Proveedor = ?");
            $stmtNombre->execute([$idEliminar]);
            $nombreEliminar = $stmtNombre->fetchColumn();

            if ($nombreEliminar === false) {
                $error = 'No se encontró el proveedor.';
            } else {
                $stmt = $pdo->prepare("DELETE FROM proveedores WHERE ID_Proveedor = ?");
                $stmt->execute([$idEliminar]);
                $exito = "Proveedor \"$nombreEliminar\" eliminado correctamente.";
            }
        } catch (PDOException $e) {
            $error = ($e->getCode() === '23000')
                ? "No se puede eliminar \"$nombreEliminar\": el proveedor tiene registros asociados."
                : 'Error al eliminar el proveedor.';
        }
    }
    $vista = 'lista';
}

// ----------------------------------------------------
// ACCIÓN: Consultar (filtro)
// ----------------------------------------------------
$filtroEmpresa = '';
$filtroCategoria = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_consultar'])) {
    $filtroEmpresa = trim($_POST['nombre_empresa_consultar'] ?? '');
    $filtroCategoria = trim($_POST['categoria_consultar'] ?? '');
    $vista = 'lista';
}

// ----------------------------------------------------
// Listado de proveedores
// ----------------------------------------------------
$sql = "SELECT pr.*, c.nombreCategoria
        FROM proveedores pr
        LEFT JOIN categorias c ON c.ID_Categoria = pr.ID_Categoria
        WHERE 1=1";
$params = [];

if ($filtroEmpresa !== '') {
    $sql .= " AND pr.nombreEmpresa LIKE ?";
    $params[] = "%$filtroEmpresa%";
}
if ($filtroCategoria !== '') {
    $sql .= " AND c.nombreCategoria LIKE ?";
    $params[] = "%$filtroCategoria%";
}
$sql .= " ORDER BY pr.nombreEmpresa";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$proveedores = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>9Eleven - Proveedores</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php require 'includes/header.php'; ?>

<div class="contenedor-principal">
    <?php require 'includes/sidebar.php'; ?>

    <main class="contenido">
        <h1>Proveedores</h1>

        <?php if ($error): ?><div class="mensaje-error"><?= h($error) ?></div><?php endif; ?>
        <?php if ($exito): ?><div class="mensaje-ok"><?= h($exito) ?></div><?php endif; ?>

        <div class="panel">
            <div class="panel-header">
                <h2>Lista de proveedores</h2>
                <div class="barra-acciones" style="margin-bottom:0;">
                    <a href="proveedores.php?vista=consultar" class="btn btn-rojo">Consultar proveedor</a>
                    <a href="proveedores.php?vista=nuevo" class="btn btn-rojo">+ Nuevo proveedor</a>
                </div>
            </div>

            <?php if ($vista === 'nuevo'): ?>
                <div class="form-box" style="margin:15px auto;">
                    <h2>Nuevo proveedor</h2>
                    <form method="POST" action="proveedores.php">
                        <div class="form-grupo">
                            <label for="nombre_proveedor">Nombre proveedor *</label>
                            <input type="text" id="nombre_proveedor" name="nombre_proveedor" required>
                        </div>
                        <div class="form-grupo">
                            <label for="contacto">Contacto</label>
                            <input type="text" id="contacto" name="contacto">
                        </div>
                        <div class="form-grupo">
                            <label for="telefono">Teléfono</label>
                            <input type="text" id="telefono" name="telefono" pattern="[0-9\+\-\(\)\s]{7,20}" title="Formatos válidos: números, espacios, guiones o paréntesis (Ej: +57 300 123 4567)">
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
                        <div class="acciones-form">
                            <a href="proveedores.php" class="btn">Cancelar</a>
                            <button type="submit" name="accion_nuevo" class="btn btn-rojo">+ Nuevo proveedor</button>
                        </div>
                    </form>
                </div>

            <?php elseif ($vista === 'editar'): 
                $idEditar = (int)($_GET['id'] ?? 0);
                $stmtEdit = $pdo->prepare("SELECT * FROM proveedores WHERE ID_Proveedor = ?");
                $stmtEdit->execute([$idEditar]);
                $provEditar = $stmtEdit->fetch();
                
                if (!$provEditar):
                    echo "<div class='mensaje-error'>Proveedor no encontrado.</div>";
                else:
            ?>
                <div class="form-box" style="margin:15px auto;">
                    <h2>Editar proveedor</h2>
                    <form method="POST" action="proveedores.php">
                        <input type="hidden" name="id_proveedor" value="<?= $provEditar['ID_Proveedor'] ?>">
                        <div class="form-grupo">
                            <label for="nombre_proveedor">Nombre proveedor *</label>
                            <input type="text" id="nombre_proveedor" name="nombre_proveedor" value="<?= h($provEditar['nombreEmpresa']) ?>" required>
                        </div>
                        <div class="form-grupo">
                            <label for="contacto">Contacto</label>
                            <input type="text" id="contacto" name="contacto" value="<?= h($provEditar['contacto']) ?>">
                        </div>
                        <div class="form-grupo">
                            <label for="telefono">Teléfono</label>
                            <input type="text" id="telefono" name="telefono" value="<?= h($provEditar['telefono']) ?>" pattern="[0-9\+\-\(\)\s]{7,20}" title="Formatos válidos: números, espacios, guiones o paréntesis (Ej: +57 300 123 4567)">
                        </div>
                        <div class="form-grupo">
                            <label for="categoria">Categoría</label>
                            <select id="categoria" name="categoria">
                                <option value="">-- Seleccionar --</option>
                                <?php foreach ($categorias as $c): ?>
                                    <option value="<?= (int)$c['ID_Categoria'] ?>" <?= ($provEditar['ID_Categoria'] == $c['ID_Categoria']) ? 'selected' : '' ?>>
                                        <?= h($c['nombreCategoria']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="acciones-form">
                            <a href="proveedores.php" class="btn">Cancelar</a>
                            <button type="submit" name="accion_editar" class="btn btn-rojo">Guardar cambios</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <?php elseif ($vista === 'consultar'): ?>
                <div class="form-box" style="margin:15px auto;">
                    <h2>Consultar proveedor</h2>
                    <form method="POST" action="proveedores.php">
                        <div class="form-grupo">
                            <label for="nombre_empresa_consultar">Nombre empresa</label>
                            <input type="text" id="nombre_empresa_consultar" name="nombre_empresa_consultar">
                        </div>
                        <div class="form-grupo">
                            <label for="categoria_consultar">Categoría</label>
                            <input type="text" id="categoria_consultar" name="categoria_consultar">
                        </div>
                        <div class="acciones-form">
                            <a href="proveedores.php" class="btn">Cancelar</a>
                            <button type="submit" name="accion_consultar" class="btn btn-rojo">Mostrar en lista</button>
                        </div>
                    </form>
                </div>

            <?php endif; ?>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Empresa</th>
                        <th>Contacto</th>
                        <th>Teléfono</th>
                        <th>Categoría</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($proveedores)): ?>
                        <tr><td colspan="6" class="sin-datos">No se encontraron proveedores.</td></tr>
                    <?php else: ?>
                        <?php foreach ($proveedores as $p): ?>
                            <tr>
                                <td>V<?= str_pad($p['ID_Proveedor'], 3, '0', STR_PAD_LEFT) ?></td>
                                <td><?= h($p['nombreEmpresa']) ?></td>
                                <td><?= h($p['contacto'] ?? '—') ?></td>
                                <td><?= h($p['telefono'] ?? '—') ?></td>
                                <td><?= h($p['nombreCategoria'] ?? '—') ?></td>
                                <td>
                                    <a href="proveedores.php?vista=editar&id=<?= (int)$p['ID_Proveedor'] ?>" style="color: #007bff; text-decoration: none; font-weight: bold;">Editar</a>
                                    <form method="POST" action="proveedores.php" style="display:inline;" onsubmit="return confirm('¿Seguro que deseas eliminar este registro?');">
                                    <?= csrfCampo() ?>
                                    <input type="hidden" name="id_eliminar" value="<?= (int)$p['ID_Proveedor'] ?>">
                                    <button type="submit" name="accion_eliminar" class="btn btn-rojo">Eliminar</button>
                                </form>
                                </td>
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
