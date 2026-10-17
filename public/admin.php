<?php
/**
 * ActaFl - Panel de Administración y Parametrización de Proyectos
 */
// Inclusión segura del backend de configuración
$configPath = __DIR__ . '/../api/config.php';
if (file_exists($configPath)) {
    require_once $configPath;
} else {
    die("Error: No se encontró el archivo de configuración en api/config.php");
}

// Control de Acceso por Rol (Superadmin o Cliente Admin)
if (!isset($_SESSION['actafl_user_id']) || !in_array($_SESSION['actafl_rol'] ?? '', ['superadmin', 'cliente_admin'])) {
    header('Location: index.html');
    exit();
}

$user_id  = $_SESSION['actafl_user_id'];
$user_rol = $_SESSION['actafl_rol'];

// Consultar lista de proyectos
try {
    if ($user_rol === 'superadmin') {
        $stmtP = $pdo->query("SELECT p.*, u.nombre as coordinador FROM proyectos p LEFT JOIN usuarios u ON p.cliente_id = u.id ORDER BY p.id DESC");
    } else {
        $stmtP = $pdo->prepare("SELECT p.*, u.nombre as coordinador FROM proyectos p LEFT JOIN usuarios u ON p.cliente_id = u.id WHERE p.cliente_id = ? ORDER BY p.id DESC");
        $stmtP->execute([$user_id]);
    }
    $proyectos = $stmtP->fetchAll();
} catch (Exception $e) {
    $proyectos = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ActaFl - Control de Proyectos</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-slate-100 font-sans pb-12">

    <!-- Navegación Superior -->
    <nav class="bg-slate-900 text-white p-4 shadow-md sticky top-0 z-40 flex justify-between items-center">
        <div class="flex items-center space-x-3">
            <h1 class="text-xl font-bold text-blue-400"><i class="fas fa-building-user mr-2"></i>ActaFl Admin</h1>
            <span class="text-xs bg-blue-500/20 text-blue-300 px-2.5 py-1 rounded-full font-semibold border border-blue-500/30">
                <?= strtoupper($user_rol) ?>
            </span>
        </div>
        <div class="flex items-center space-x-4">
            <span class="text-sm text-slate-300"><?= htmlspecialchars($_SESSION['actafl_nombre'] ?? 'Usuario') ?></span>
            <a href="../api/logout.php" class="bg-red-500/20 text-red-300 hover:bg-red-600 hover:text-white px-3 py-1.5 rounded-xl text-xs font-bold transition-all">
                <i class="fas fa-power-off mr-1"></i> Salir
            </a>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto p-4 md:p-6 space-y-6">
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <h2 class="text-2xl font-bold text-slate-800">Gestión de Obras & Parametrización</h2>
            <p class="text-slate-500 text-sm mt-1">Sistemas de actas de vecindad para obras civiles.</p>
        </div>

        <!-- Lista de Proyectos -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach($proyectos as $p): ?>
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded font-bold">ID #<?= $p['id'] ?></span>
                <h3 class="text-lg font-bold text-slate-800 mt-2"><?= htmlspecialchars($p['nombre']) ?></h3>
                <p class="text-xs text-slate-500 mt-1"><?= htmlspecialchars($p['descripcion'] ?? 'Sin descripción') ?></p>
            </div>
            <?php endforeach; ?>

            <?php if(empty($proyectos)): ?>
            <div class="col-span-full bg-white p-12 text-center rounded-2xl border border-dashed border-slate-300 text-slate-400">
                No hay proyectos registrados o activos.
            </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>