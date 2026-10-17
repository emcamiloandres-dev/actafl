<?php
/**
 * ActaFl API - Endpoint de Sincronización de Inspecciones
 */
require 'config.php';

// Validar que la solicitud sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit();
}

// Obtener la carga JSON
$input = json_decode(file_get_contents("php://input"), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Carga de datos JSON inválida.']);
    exit();
}

$inspector_id = $_SESSION['actafl_user_id'] ?? ($input['inspector_id'] ?? null);
$proyecto_id  = $input['proyecto_id'] ?? null;
$latitud      = $input['latitud'] ?? null;
$longitud     = $input['longitud'] ?? null;
$datos_json   = json_encode($input['datos_acta'] ?? []);
$fotos        = $input['fotos'] ?? []; // Array de imágenes codificadas en Base64

if (!$inspector_id || !$proyecto_id) {
    echo json_encode(['success' => false, 'message' => 'Identificador de proyecto e inspector requeridos.']);
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. Insertar el registro principal de la inspección
    $stmt = $pdo->prepare("
        INSERT INTO inspecciones (proyecto_id, inspector_id, latitud, longitud, datos_json, fecha_sincronizacion) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$proyecto_id, $inspector_id, $latitud, $longitud, $datos_json]);
    $inspeccion_id = $pdo->lastInsertId();

    // 2. Procesar y guardar fotografías enviadas
    if (!empty($fotos) && is_array($fotos)) {
        $stmtFoto = $pdo->prepare("INSERT INTO inspeccion_fotos (inspeccion_id, ruta_foto) VALUES (?, ?)");
        
        // Asegurar que exista la carpeta física para guardar imágenes
        $dirUploads = __DIR__ . '/../public/uploads/';
        if (!file_exists($dirUploads)) {
            mkdir($dirUploads, 0755, true);
        }

        foreach ($fotos as $index => $base64Img) {
            if (preg_match('/^data:image\/(\w+);base64,/', $base64Img, $type)) {
                $data = substr($base64Img, strpos($base64Img, ',') + 1);
                $type = strtolower($type[1]); // png, jpg, jpeg
                $data = base64_decode($data);

                if ($data !== false) {
                    $nombreFoto = 'acta_' . $inspeccion_id . '_' . time() . '_' . $index . '.' . $type;
                    file_put_contents($dirUploads . $nombreFoto, $data);
                    
                    // Guardar ruta relativa en la BD
                    $stmtFoto->execute([$inspeccion_id, 'uploads/' . $nombreFoto]);
                }
            }
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Acta de inspección sincronizada con éxito.',
        'inspeccion_id' => $inspeccion_id
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Error al guardar la inspección: ' . $e->getMessage()]);
}