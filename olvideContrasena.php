<?php
require_once 'config.php';
require_once 'includes/correo.php';

$error   = '';
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_olvide'])) {
    $email = trim($_POST['email'] ?? '');

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Ingresa un correo electrónico válido.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT ID_Administrador, email FROM administrador WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $admin = $stmt->fetch();

            if ($admin) {
                // Anti-spam: si ya se generó un enlace hace menos de 60 segundos, no se crea otro.
                $reciente = $pdo->prepare(
                    "SELECT COUNT(*) FROM recuperacion_contrasena
                     WHERE ID_Administrador = ? AND creado > (NOW() - INTERVAL 60 SECOND)"
                );
                $reciente->execute([$admin['ID_Administrador']]);

                if ((int)$reciente->fetchColumn() === 0) {
                    $minutos = (int)(configCorreo()['minutos_expiracion'] ?? 30);
                    if ($minutos < 1) {
                        $minutos = 30;
                    }

                    // El token se envía por correo; en la BD solo se guarda su hash.
                    $token = bin2hex(random_bytes(32));
                    $hash  = hash('sha256', $token);

                    $pdo->beginTransaction();
                    // Los enlaces anteriores sin usar dejan de servir.
                    $pdo->prepare("UPDATE recuperacion_contrasena SET usado = 1 WHERE ID_Administrador = ? AND usado = 0")
                        ->execute([$admin['ID_Administrador']]);
                    $pdo->prepare(
                        "INSERT INTO recuperacion_contrasena (ID_Administrador, token_hash, expira)
                         VALUES (?, ?, DATE_ADD(NOW(), INTERVAL $minutos MINUTE))"
                    )->execute([$admin['ID_Administrador'], $hash]);
                    $pdo->commit();

                    $enlace = urlBaseApp() . '/restablecerContrasena.php?token=' . $token;
                    $texto  = "Hola,\n\n"
                            . "Recibimos una solicitud para restablecer la contraseña de tu cuenta de 9Eleven.\n\n"
                            . "Abre este enlace para elegir una nueva contraseña "
                            . "(vence en $minutos minutos y solo funciona una vez):\n\n"
                            . $enlace . "\n\n"
                            . "Si no fuiste tú, ignora este mensaje: tu contraseña no cambiará.\n";

                    if (!enviarCorreo($admin['email'], '9Eleven - Restablecer contraseña', $texto)) {
                        error_log('9Eleven: falló el envío del correo de recuperación.');
                    }
                }
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('9Eleven recuperación: ' . $e->getMessage());
        }

        // Mismo mensaje exista o no el correo, para no revelar qué cuentas existen.
        $mensaje = 'Si el correo está registrado, te enviamos un enlace para restablecer la contraseña. '
                 . 'Revisa tu bandeja de entrada y la carpeta de spam.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>9Eleven - Recuperar contraseña</title>
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
        <h1>Recuperar contraseña</h1>

        <?php if ($error): ?>
            <div class="mensaje-error"><?= h($error) ?></div>
        <?php endif; ?>
        <?php if ($mensaje): ?>
            <div class="mensaje-ok"><?= h($mensaje) ?></div>
        <?php else: ?>
            <p>Escribe el correo de tu cuenta y te enviaremos un enlace para elegir una nueva contraseña.</p>
            <form method="POST" action="olvideContrasena.php">
                <div class="form-grupo">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required autofocus>
                </div>
                <button type="submit" name="accion_olvide" class="btn btn-rojo btn-ancho">Enviar enlace</button>
            </form>
        <?php endif; ?>

        <div class="link-secundario">
            <a href="index.php">Volver a Log In</a>
        </div>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
</body>
</html>
