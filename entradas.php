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

// Qué pantalla mostrar: el listado (por defecto) o el formulario de nueva entrada
$vista = (($_GET['vista'] ?? '') === 'nueva') ? 'nueva' : 'lista';

// Catálogos para el formulario
$proveedores = $pdo->query("SELECT ID_Proveedor, nombreEmpresa FROM proveedores ORDER BY nombreEmpresa")->fetchAll();
$productos   = $pdo->query("SELECT ID_Producto, codigoProducto, nombreProducto, precioCompra FROM productos ORDER BY nombreProducto")->fetchAll();

/**
 * Devuelve el HTML de una fila de producto del formulario de entrada.
 */
function filaEntrada(array $productos): string
{
    $html  = '<tr>';
    $html .= '<td><select name="producto[]" class="in-prod"><option value="">-- Seleccionar --</option>';
    foreach ($productos as $p) {
        $html .= '<option value="' . (int)$p['ID_Producto'] . '" data-precio="' . h((string)$p['precioCompra']) . '">'
               . h($p['codigoProducto'] . ' - ' . $p['nombreProducto']) . '</option>';
    }
    $html .= '</select></td>';
    $html .= '<td><input type="number" name="cantidad[]" class="in-cant" min="1" step="1"></td>';
    $html .= '<td><input type="number" name="precio[]" class="in-precio" min="0" step="0.01"></td>';
    $html .= '<td class="sub">$0</td>';
    $html .= '<td><button type="button" class="btn quitar" title="Quitar fila">✕</button></td>';
    $html .= '</tr>';
    return $html;
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

        <div class="barra-acciones">
            <a href="entradas.php" class="btn">Ver entradas</a>
            <a href="entradas.php?vista=nueva" class="btn btn-rojo">+ Nueva entrada</a>
        </div>

        <?php if ($vista === 'nueva'): ?>
            <            <div class="form-box" style="max-width:820px;">
                <h2>Nueva entrada de mercancía</h2>
                <form method="POST" action="entradas.php?vista=nueva">
                    <div class="form-grupo">
                        <label for="fecha">Fecha</label>
                        <input type="date" id="fecha" name="fecha" value="<?= h(date('Y-m-d')) ?>" max="<?= h(date('Y-m-d')) ?>" required>
                    </div>
                    <div class="form-grupo">
                        <label for="proveedor">Proveedor</label>
                        <select id="proveedor" name="proveedor" required>
                            <option value="">-- Seleccionar --</option>
                            <?php foreach ($proveedores as $pv): ?>
                                <option value="<?= (int)$pv['ID_Proveedor'] ?>"><?= h($pv['nombreEmpresa']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <h2 style="margin-top:18px;">Productos recibidos</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th style="width:110px;">Cantidad</th>
                                <th style="width:140px;">Precio unitario</th>
                                <th style="width:110px;">Subtotal</th>
                                <th style="width:50px;"></th>
                            </tr>
                        </thead>
                        <tbody id="lineas">
                            <?= filaEntrada($productos) ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" style="text-align:right; font-weight:bold;">Total de la entrada:</td>
                                <td colspan="2" style="font-weight:bold;" id="total-entrada">$0</td>
                            </tr>
                        </tfoot>
                    </table>

                    <template id="plantilla-fila"><?= filaEntrada($productos) ?></template>

                    <div style="margin-top:12px;">
                        <button type="button" class="btn" id="agregar-fila">+ Agregar producto</button>
                    </div>

                    <div class="acciones-form">
                        <a href="entradas.php" class="btn">Cancelar</a>
                        <button type="submit" name="accion_nueva" class="btn btn-rojo">REGISTRAR ENTRADA</button>
                    </div>
                </form>
            </div>>
        <?php else: ?>

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

            <style>
                #lineas select, #lineas input {
                    width: 100%; padding: 6px 8px; border: none; border-radius: 4px;
                    background-color: var(--azul-hielo); color: #1a1a1a; font-size: 0.85rem;
                }
            </style>
            <script>
            (function () {
                var cuerpo = document.getElementById('lineas');
                var plantilla = document.getElementById('plantilla-fila');
                var totalEl = document.getElementById('total-entrada');

                function pesos(n) { return '$' + Math.round(n).toLocaleString('es-CO'); }

                function recalcular() {
                    var total = 0;
                    cuerpo.querySelectorAll('tr').forEach(function (tr) {
                        var c = parseFloat(tr.querySelector('.in-cant').value) || 0;
                        var p = parseFloat(tr.querySelector('.in-precio').value) || 0;
                        tr.querySelector('.sub').textContent = pesos(c * p);
                        total += c * p;
                    });
                    totalEl.textContent = pesos(total);
                }

                document.getElementById('agregar-fila').addEventListener('click', function () {
                    cuerpo.appendChild(plantilla.content.querySelector('tr').cloneNode(true));
                    recalcular();
                });

                cuerpo.addEventListener('input', recalcular);

                cuerpo.addEventListener('change', function (e) {
                    if (e.target.classList.contains('in-prod')) {
                        var tr = e.target.closest('tr');
                        var opcion = e.target.options[e.target.selectedIndex];
                        var precio = tr.querySelector('.in-precio');
                        // Sugiere el precio de compra del producto si todavía no se escribió uno
                        if (opcion && opcion.dataset.precio && precio.value === '') {
                            precio.value = opcion.dataset.precio;
                        }
                        recalcular();
                    }
                });

                cuerpo.addEventListener('click', function (e) {
                    if (e.target.classList.contains('quitar')) {
                        var filas = cuerpo.querySelectorAll('tr');
                        var tr = e.target.closest('tr');
                        if (filas.length > 1) {
                            tr.remove();
                        } else {
                            tr.querySelector('.in-prod').value = '';
                            tr.querySelector('.in-cant').value = '';
                            tr.querySelector('.in-precio').value = '';
                        }
                        recalcular();
                    }
                });

                recalcular();
            })();
            </script>
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

        <?php endif; ?>
    </main>
</div>

<?php require 'includes/footer.php'; ?>
</body>
</html>