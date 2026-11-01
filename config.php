<?php
/**
 * config.php
 * Conexión a la base de datos + helpers de sesión/autenticación.
 * Este archivo se incluye al inicio de TODAS las páginas del sistema.
 */

session_start();

// ----------------------------------------------------
// Conexión a la base de datos (XAMPP - MySQL/MariaDB)
// ----------------------------------------------------
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'db_9eleven');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . htmlspecialchars($e->getMessage()));
}

// ----------------------------------------------------
// Helpers de autenticación
// ----------------------------------------------------

/**
 * Devuelve true si hay un administrador con sesión activa.
 */
function estaLogueado(): bool
{
    return isset($_SESSION['admin_id']);
}

/**
 * Obliga a que exista sesión activa; si no, redirige al login.
 * Se llama al inicio de toda página protegida (dashboard, productos, etc).
 */
function requerirLogin(): void
{
    if (!estaLogueado()) {
        header('Location: index.php');
        exit;
    }
}

/**
 * Escapa texto para salida segura en HTML.
 */
function h(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Formatea un número como pesos colombianos. Ej: 1200 -> $1.200
 */
function formatoPesos($valor): string
{
    return '$' . number_format((float)$valor, 0, ',', '.');
}
