<?php
require_once 'config.php';

// Asegurar que la sesion este iniciada para poder rastrear los intentos
if (session_status() === PHP_SESSION_NONE) 
{
    session_start();
}

$error = '';
$exito = '';

// Configuracion de bloqueo
$maxIntentos = 5;
$tiempoBloqueo = 5 * 60; // 5 minutos en segundos

// Verificar si el usuario esta bloqueado actualmente
if (isset($_SESSION['bloqueo_hasta'])) 
    {
    if (time() < $_SESSION['bloqueo_hasta']) 
    {
        $error = 'Demasiados intentos fallidos. Por favor, espera unos minutos antes de volver a intentarlo.';
    } else 
    {
        // El tiempo de bloqueo paso, restablecemos las variables
        unset($_SESSION['intentos_fallidos']);
        unset($_SESSION['bloqueo_hasta']);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_cambiar']) && $error ==='') 
{
    $email          = trim($_POST['email'] ?? '');
    $claveActual    = $_POST['clave_actual'] ?? '';
    $claveNueva     = $_POST['clave_nueva'] ?? '';
    $claveConfirmar = $_POST['clave_confirmar'] ?? '';

    if ($email === '' || $claveActual === '' || $claveNueva === '' || $claveConfirmar === '') {
        $error = 'Todos los campos son obligatorios.';
    } elseif ($claveNueva !== $claveConfirmar) {
        $error = 'La nueva contraseña y su confirmación no coinciden.';
    } elseif (strlen($claveNueva) < 6) {
        $error = 'La nueva contraseña debe tener al menos 6 caracteres.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM administrador WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        $claveValida = false;
        if ($admin) {
            if (password_get_info($admin['contrasena'])['algo'] !== null) {
                $claveValida = password_verify($claveActual, $admin['contrasena']);
            } else {
                $claveValida = hash_equals($admin['contrasena'], $claveActual);
            }
        }

        if (!$admin || !$claveValida) 
        {
            $error = 'Verifica los datos ingresados, algo esta incorrecto.';
            //Registra intentos fallidos
            $_SESSION['intentos_fallidos'] = ($_SESSION['intentos_fallidos'] ?? 0) + 1;
            //Si alcanza el limite aplica el bloqueo
            if ($_SESSION['intentos_fallidos'] >= $maxIntentos)
            {
                $_SESSION['bloque_hasta'] =time() + $tiempoBloqueo;
                $error = 'demasiados intentos fallidos. porfavor intente de nuevo despues de unos minutos';
            }

        } else 
        {
            $hash = password_hash($claveNueva, PASSWORD_DEFAULT);
            $upd = $pdo->prepare("UPDATE administrador SET contrasena = ? WHERE ID_Administrador = ?");
            $upd->execute([$hash, $admin['ID_Administrador']]);
            $exito = 'Contraseña actualizada correctamente. Ya puedes iniciar sesión.';

            // Resetear los intentos fallidos tras un exito
            unset($_SESSION['intentos_fallidos']);
            unset($_SESSION['bloqueo_hasta']);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>9Eleven - Cambiar contraseña</title>
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
        <h1>Cambiar contraseña</h1>

        <?php if ($error): ?>
            <div class="mensaje-error"><?= h($error) ?></div>
        <?php endif; ?>
        <?php if ($exito): ?>
            <div class="mensaje-ok"><?= h($exito) ?></div>
        <?php endif; ?>

        <form method="POST" action="cambiarContrasena.php">
            <div class="form-grupo">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-grupo">
                <label for="clave_actual">Contraseña actual</label>
                <input type="password" id="clave_actual" name="clave_actual" required>
            </div>
            <div class="form-grupo">
                <label for="clave_nueva">Nueva contraseña</label>
                <input type="password" id="clave_nueva" name="clave_nueva" required>
            </div>
            <div class="form-grupo">
                <label for="clave_confirmar">Confirmar contraseña</label>
                <input type="password" id="clave_confirmar" name="clave_confirmar" required>
            </div>
            <button type="submit" name="accion_cambiar" class="btn btn-rojo btn-ancho" <?=isset($_SESSION['bloqueo_hasta']) && time() < $_SESSION['bloqueo_hasta'] ? 'disabled style="opacity: 0.5;"': '' ?>>Cambiar contraseña</button>
        </form>

        <div class="link-secundario">
            <a href="index.php">Volver a Log In</a>
        </div>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
</body>
</html>