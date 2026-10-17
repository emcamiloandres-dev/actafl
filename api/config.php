<?php
/**
 * ActaFl API - Conexión PDO con Diagnóstico Flexible de .env
 */
header('Content-Type: application/json; charset=utf-8');

session_name('ACTAFL_SESSION');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Carga liviana de variables de entorno
 */
function cargarEnv($ruta) {
    if (!file_exists($ruta)) return false;
    $lineas = file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lineas as $linea) {
        $linea = trim($linea);
        if ($linea === '' || strpos($linea, '#') === 0) continue;
        if (strpos($linea, '=') !== false) {
            list($nombre, $valor) = explode('=', $linea, 2);
            $_ENV[trim($nombre)] = trim($valor);
        }
    }
    return true;
}

// Probar múltiples ubicaciones posibles del .env en Hostinger
$rutasEnv = [
    dirname(__DIR__) . '/.env',                 // Raíz del proyecto
    dirname(__DIR__, 2) . '/.env',              // Un nivel arriba de public_html
    $_SERVER['DOCUMENT_ROOT'] . '/../.env',     // Relativo al DocumentRoot
    __DIR__ . '/../.env'                        // Resguardo local
];

foreach ($rutasEnv as $ruta) {
    if (cargarEnv($ruta)) {
        break;
    }
}

// Extraer credenciales cargadas desde el .env
$host = $_ENV['DB_HOST'] ?? 'localhost';
$db   = $_ENV['DB_NAME'] ?? 'u220899471_actafl';
$user = $_ENV['DB_USER'] ?? 'u220899471_actafl';
$pass = $_ENV['DB_PASS'] ?? '';

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
    echo json_encode([
        'success' => false,
        'error'   => 'Error de conexión a la base de datos de ActaFl',
        'detalle' => $e->getMessage() // Permite diagnosticar la causa exacta si falla la clave o el host
    ], JSON_UNESCAPED_UNICODE);
    exit();
}