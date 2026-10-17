<?php
/**
 * ActaFl API - Autenticación de Usuarios / Inspectores
 */
require 'config.php';

$input = json_decode(file_get_contents("php://input"), true);

$email    = trim($input['email'] ?? '');
$password = trim($input['password'] ?? '');

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Ingresa correo electrónico y contraseña.']);
    exit();
}

try {
    // Buscar usuario en la base de datos
    $stmt = $pdo->prepare("SELECT id, nombre, email, password, rol, activo FROM usuarios WHERE email = ? AND activo = 1");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if ($usuario && password_verify($password, $usuario['password'])) {
        // Guardar sesión del usuario
        $_SESSION['actafl_user_id'] = $usuario['id'];
        $_SESSION['actafl_nombre']  = $usuario['nombre'];
        $_SESSION['actafl_rol']     = $usuario['rol'];

        echo json_encode([
            'success' => true,
            'message' => 'Inicio de sesión exitoso',
            'user'    => [
                'id'     => $usuario['id'],
                'nombre' => $usuario['nombre'],
                'email'  => $usuario['email'],
                'rol'    => $usuario['rol']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Credenciales incorrectas o usuario inactivo.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error al procesar el inicio de sesión.']);
}