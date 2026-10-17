<?php
/**
 * ActaFl API - Gestión de Proyectos y Parametrización de Formularios
 */
require 'config.php';

// Verificar autenticación
if (!isset($_SESSION['actafl_user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit();
}

$user_id  = $_SESSION['actafl_user_id'];
$user_rol = $_SESSION['actafl_rol'];
$metodo   = $_SERVER['REQUEST_METHOD'];

// Manejo de peticiones GET
if ($metodo === 'GET') {
    $accion = $_GET['accion'] ?? '';

    if ($accion === 'listar_campos') {
        $proyecto_id = intval($_GET['proyecto_id'] ?? 0);
        if ($proyecto_id <= 0) {
            echo json_encode([]);
            exit();
        }

        $stmt = $pdo->prepare("SELECT * FROM proyecto_campos_formulario WHERE proyecto_id = ? ORDER BY orden ASC, id ASC");
        $stmt->execute([$proyecto_id]);
        echo json_encode($stmt->fetchAll());
        exit();
    }
}

// Manejo de peticiones POST
if ($metodo === 'POST') {
    $input = json_decode(file_get_contents("php://input"), true) ?? $_POST;
    $accion = $input['accion'] ?? '';

    // 1. Crear Nuevo Proyecto
    if ($accion === 'crear_proyecto') {
        if (!in_array($user_rol, ['superadmin', 'cliente_admin'])) {
            echo json_encode(['success' => false, 'message' => 'Sin permisos para crear proyectos.']);
            exit();
        }

        $nombre      = trim($input['nombre'] ?? '');
        $descripcion = trim($input['descripcion'] ?? '');

        if (empty($nombre)) {
            header('Location: ../public/admin.php?error=nombre_requerido');
            exit();
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO proyectos (nombre, descripcion, cliente_id, activo) VALUES (?, ?, ?, 1)");
            $stmt->execute([$nombre, $descripcion, $user_id]);
            header('Location: ../public/admin.php?res=proyecto_creado');
            exit();
        } catch (PDOException $e) {
            header('Location: ../public/admin.php?error=db_error');
            exit();
        }
    }

    // 2. Agregar Campo Parametrizado a la Plantilla del Proyecto
    if ($accion === 'agregar_campo') {
        $proyecto_id = intval($input['proyecto_id'] ?? 0);
        $label_campo = trim($input['label_campo'] ?? '');
        $tipo_campo  = $input['tipo_campo'] ?? 'texto';

        if ($proyecto_id <= 0 || empty($label_campo)) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
            exit();
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO proyecto_campos_formulario (proyecto_id, label_campo, tipo_campo) VALUES (?, ?, ?)");
            $stmt->execute([$proyecto_id, $label_campo, $tipo_campo]);
            echo json_encode(['success' => true, 'message' => 'Campo agregado exitosamente.']);
            exit();
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Error al guardar el campo.']);
            exit();
        }
    }

    // 3. Eliminar Campo de la Plantilla
    if ($accion === 'eliminar_campo') {
        $campo_id = intval($input['campo_id'] ?? 0);
        if ($campo_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID de campo inválido.']);
            exit();
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM proyecto_campos_formulario WHERE id = ?");
            $stmt->execute([$campo_id]);
            echo json_encode(['success' => true, 'message' => 'Campo eliminado.']);
            exit();
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar el campo.']);
            exit();
        }
    }
}