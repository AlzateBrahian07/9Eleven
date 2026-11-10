<?php
require_once 'config.php';

// Que el token de la URL no se filtre a otros sitios por el encabezado Referer.
header('Referrer-Policy: no-referrer');

$error  = '';
$exito  = '';
$valido = false;

$token = $_POST['token'] ?? $_GET['token'] ?? '';
$token = is_string($token) ? trim($token) : '';

/**
 * Busca un enlace vigente (no usado y no vencido) a partir del token recibido.
 */
function buscarRecuperacion(PDO $pdo, string $token): ?array
{
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        return null;
    }
    $stmt = $pdo->prepare(
        "SELECT ID_Recuperacion, ID_Administrador
         FROM recuperacion_contrasena
         WHERE token_hash = ? AND usado = 0 AND expira > NOW()
         LIMIT 1"
    );
    $stmt->execute([hash('sha256', $token)]);
    $fila = $stmt->fetch();
    return $fila ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_restablecer'])) {
    $claveNueva     = $_POST['clave_nueva'] ?? '';
    $claveConfirmar = $_POST['clave_confirmar'] ?? '';
    $rec = buscarRecuperacion($pdo, $token);

    if (!$rec) {
        $error = 'El enlace no es válido o ya expiró. Solicita uno nuevo.';
    } elseif ($claveNueva === '' || $claveConfirmar === '') {
        $valido = true;
        $error  = 'Todos los campos son obligatorios.';
    } elseif ($claveNueva !== $claveConfirmar) {
        $valido = true;
        $error  = 'La nueva contraseña y su confirmación no coinciden.';
    } elseif (strlen($claveNueva) < 6) {
        $valido = true;
        $error  = 'La nueva contraseña debe tener al menos 6 caracteres.';
    } else {
        try {
            $pdo->beginTransaction();

            // Marcar el enlace como usado; si otra petición ya lo usó, rowCount será 0.
            $marcar = $pdo->prepare(
                "UPDATE recuperacion_contrasena SET usado = 1
                 WHERE ID_Recuperacion = ? AND usado = 0 AND expira > NOW()"
            );
            $marcar->execute([$rec['ID_Recuperacion']]);

            if ($marcar->rowCount() !== 1) {
                $pdo->rollBack();
                $error = 'El enlace no es válido o ya expiró. Solicita uno nuevo.';
            } else {
                $pdo->prepare("UPDATE administrador SET contrasena = ? WHERE ID_Administrador = ?")
                    ->execute([password_hash($claveNueva, PASSWORD_DEFAULT), $rec['ID_Administrador']]);
                // Cualquier otro enlace pendiente de esta cuenta también queda invalidado.
                $pdo->prepare("UPDATE recuperacion_contrasena SET usado = 1 WHERE ID_Administrador = ? AND usado = 0")
                    ->execute([$rec['ID_Administrador']]);
                $pdo->commit();
                $exito = 'Contraseña actualizada correctamente. Ya puedes iniciar sesión.';
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('9Eleven restablecer: ' . $e->getMessage());
            $error = 'No se pudo actualizar la contraseña. Inténtalo de nuevo.';
            $valido = true;
        }
    }
} else {
    $valido = buscarRecuperacion($pdo, $token) !== null;
    if (!$valido) {
        $error = 'El enlace no es válido o ya expiró. Solicita uno nuevo.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="referrer" content="no-referrer">
    <title>9Eleven - Restablecer contraseña</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="header">
    <div class="logo">
        <span style="font-weight:bold; font-size:1.4rem; letter-spacing:1px;">9ELEVEN</span>
    </div>
    <div class="usuario">
        <a href="index.php">Volver al inicio</a>
        <div class="avatar"></div>
    </div>
</header>

<div class="login-wrapper">
    <div class="login-caja">
        <div class="avatar-grande"></div>
        <h1>Restablecer contraseña</h1>

        <?php if ($error): ?>
            <div class="mensaje-error"><?= h($error) ?></div>
        <?php endif; ?>
        <?php if ($exito): ?>
            <div class="mensaje-ok"><?= h($exito) ?></div>
        <?php endif; ?>

        <?php if ($valido && !$exito): ?>
            <form method="POST" action="restablecerContrasena.php">
                <input type="hidden" name="token" value="<?= h($token) ?>">
                <div class="form-grupo">
                    <label for="clave_nueva">Nueva contraseña</label>
                    <input type="password" id="clave_nueva" name="clave_nueva" required autofocus>
                </div>
                <div class="form-grupo">
                    <label for="clave_confirmar">Confirmar contraseña</label>
                    <input type="password" id="clave_confirmar" name="clave_confirmar" required>
                </div>
                <button type="submit" name="accion_restablecer" class="btn btn-rojo btn-ancho">Guardar contraseña</button>
            </form>
        <?php elseif (!$exito): ?>
            <div class="link-secundario">
                <a href="olvideContrasena.php">Solicitar un enlace nuevo</a>
            </div>
        <?php endif; ?>

        <div class="link-secundario">
            <a href="index.php">Volver a Log In</a>
        </div>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
</body>
</html>
