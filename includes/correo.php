<?php
/**
 * includes/correo.php
 * Configuración y envío de correos (recuperación de contraseña).
 *
 * Dos modos (ver config_mail.example.php):
 *  - 'log':  escribe el correo en storage/correos.log (pruebas locales, sin SMTP).
 *  - 'smtp': envía con PHPMailer (hay que copiar PHPMailer en lib/PHPMailer/src/).
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

/**
 * Devuelve la configuración de correo (valores por defecto + config_mail.php si existe).
 */
function configCorreo(): array
{
    static $cfg = null;
    if ($cfg !== null) {
        return $cfg;
    }

    $cfg = [
        'modo'               => 'log',
        'app_url'            => '',
        'minutos_expiracion' => 30,
        'smtp_host'          => '',
        'smtp_port'          => 587,
        'smtp_cifrado'       => 'tls',
        'smtp_usuario'       => '',
        'smtp_clave'         => '',
        'remitente_correo'   => '',
        'remitente_nombre'   => '9Eleven Inventario',
    ];

    $archivo = __DIR__ . '/../config_mail.php';
    if (is_file($archivo)) {
        $propia = require $archivo;
        if (is_array($propia)) {
            $cfg = array_merge($cfg, $propia);
        }
    }
    return $cfg;
}

/**
 * URL base del proyecto, sin barra final.
 * Usa 'app_url' de config_mail.php; si está vacío, la deduce de la petición.
 */
function urlBaseApp(): string
{
    $url = trim((string)(configCorreo()['app_url'] ?? ''));
    if ($url !== '') {
        return rtrim($url, '/');
    }
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $host  = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir   = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    return ($https ? 'https' : 'http') . '://' . $host . $dir;
}

/**
 * Guarda el correo en storage/correos.log (modo 'log').
 */
function guardarCorreoEnLog(string $para, string $asunto, string $mensaje): bool
{
    $dir = __DIR__ . '/../storage';
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        error_log('9Eleven: no se pudo crear la carpeta storage/');
        return false;
    }
    $texto = '[' . date('Y-m-d H:i:s') . "] Para: $para\nAsunto: $asunto\n\n$mensaje\n"
           . str_repeat('-', 60) . "\n";
    return file_put_contents($dir . '/correos.log', $texto, FILE_APPEND | LOCK_EX) !== false;
}

/**
 * Envía un correo de texto plano. Devuelve true si salió bien.
 * Nunca lanza excepciones: los errores quedan en el log de PHP/Apache.
 */
function enviarCorreo(string $para, string $asunto, string $mensaje): bool
{
    $cfg = configCorreo();

    if (($cfg['modo'] ?? 'log') !== 'smtp') {
        return guardarCorreoEnLog($para, $asunto, $mensaje);
    }

    $base = __DIR__ . '/../lib/PHPMailer/src/';
    foreach (['Exception.php', 'PHPMailer.php', 'SMTP.php'] as $archivo) {
        if (!is_file($base . $archivo)) {
            error_log('9Eleven: falta PHPMailer en lib/PHPMailer/src/ (' . $archivo . ')');
            return false;
        }
    }
    require_once $base . 'Exception.php';
    require_once $base . 'PHPMailer.php';
    require_once $base . 'SMTP.php';

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $cfg['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $cfg['smtp_usuario'];
        $mail->Password   = $cfg['smtp_clave'];
        $mail->SMTPSecure = ($cfg['smtp_cifrado'] === 'ssl')
            ? PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int)$cfg['smtp_port'];
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($cfg['remitente_correo'], $cfg['remitente_nombre']);
        $mail->addAddress($para);
        $mail->isHTML(false);
        $mail->Subject = $asunto;
        $mail->Body    = $mensaje;

        $mail->send();
        return true;
    } catch (MailException $e) {
        error_log('9Eleven: no se pudo enviar el correo: ' . $e->getMessage());
        return false;
    }
}
