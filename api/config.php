<?php
/**
 * ActaFl API - Conexión Segura PDO con Carga de .env
 */
header('Content-Type: application/json');

// Manejo de Sesión Aislado
session_name('ACTAFL_SESSION');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Función liviana para parsear archivos .env sin dependencias de Composer
 */
function cargarEnv($ruta) {
    if (!file_exists($ruta)) return false;
    $lineas = file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lineas as $linea) {
        if (strpos(trim($linea), '#') === 0) continue;
        if (strpos($linea, '=') !== false) {
            list($nombre, $valor) = explode('=', $linea, 2);
            $_ENV[trim($nombre)] = trim($valor);
        }
    }
    return true;
}

// 1. Carga desde ubicación de producción segura (un nivel arriba de public_html)
$rutaSegura = dirname(__DIR__, 2) . '/.env';

if (!cargarEnv($rutaSegura)) {
    // 2. Resguardo para entorno local Docker
    cargarEnv(__DIR__ . '/../.env');
}

$host = $_ENV['DB_HOST'] ?? 'db'; // 'db' es el servicio del contenedor MySQL local
$db   = $_ENV['DB_NAME'] ?? 'actafl_db';
$user = $_ENV['DB_USER'] ?? 'actafl_user';
$pass = $_ENV['DB_PASS'] ?? 'user_password_local';

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $pdo->exec("SET time_zone = '-05:00'");
    date_default_timezone_set('America/Bogota');
} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos']);
    exit();
}