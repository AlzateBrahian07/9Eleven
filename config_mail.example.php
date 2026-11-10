<?php
/**
 * config_mail.example.php
 * Copia este archivo como config_mail.php y ajusta los valores.
 * config_mail.php está en .gitignore: NO lo subas al repositorio.
 */
return [
    // 'log'  -> no envía correos: guarda el mensaje en storage/correos.log (útil para probar en local).
    // 'smtp' -> envía el correo de verdad con PHPMailer.
    'modo' => 'log',

    // URL donde corre el proyecto (sin barra final). Se usa para armar el enlace del correo.
    'app_url' => 'http://localhost/9Eleven',

    // Minutos que dura el enlace de recuperación.
    'minutos_expiracion' => 30,

    // --- Solo para modo 'smtp' ---
    'smtp_host'    => 'smtp.gmail.com',
    'smtp_port'    => 587,
    'smtp_cifrado' => 'tls',                       // 'tls' (puerto 587) o 'ssl' (puerto 465)
    'smtp_usuario' => 'tu-correo@gmail.com',
    'smtp_clave'   => 'contraseña-de-aplicacion',  // en Gmail: contraseña de aplicación, no la normal

    'remitente_correo' => 'tu-correo@gmail.com',
    'remitente_nombre' => '9Eleven Inventario',
];
