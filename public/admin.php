<?php
/**
 * ActaFl - Panel de Administración y Parametrización de Proyectos
 */
require_once '../api/config.php';

// Control de Acceso por Rol (Superadmin o Cliente Admin)
if (!isset($_SESSION['actafl_user_id']) \vert{}\vert{} !in_array($_SESSION['actafl_rol'], ['superadmin', 'cliente_admin'])) {
    header('Location: index.html');
    exit();
}

$user_id  =$_SESSION['actafl_user_id'];
$user_rol =$_SESSION['actafl_rol'];

// Consultar lista de proyectos según jerarquía de rol
if ($user_rol === 'superadmin') {
    $stmtP =$pdo->query("SELECT p.*, u.nombre as coordinador FROM proyectos p LEFT JOIN usuarios u ON p.cliente_id = u.id ORDER BY p.id DESC");
} else {
    $stmtP =$pdo->prepare("SELECT p.*, u.nombre as coordinador FROM proyectos p LEFT JOIN usuarios u ON p.cliente_id = u.id WHERE p.cliente_id = ? ORDER BY p.id DESC");
    $stmtP->execute([$user_id]);
}
$proyectos =$stmtP->fetchAll();

// Consultar usuarios disponibles para asignación (Inspectores / Coordinadores)
$usuarios =$pdo->query("SELECT id, nombre, email, rol FROM usuarios WHERE activo = 1 ORDER BY nombre ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ActaFl - Control de Proyectos & Formularios</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f8fafc; font-family: system-ui, -apple-system, sans-serif; }
    </style>
</head>
<body class="pb-12">

    <!-- Navegación Superior -->
    <nav class="bg-slate-900 text-white p-4 shadow-md sticky top-0 z-40 flex justify-between items-center">
        <div class="flex items-center space-x-3">
            <h1 class="text-xl font-bold tracking-tight text-blue-400"><i class="fas fa-building-user mr-2"></i>ActaFl Admin</h1>
            <span class="text-xs bg-blue-500/20 text-blue-300 px-2.5 py-1 rounded-full font-semibold border border-blue-500/30">
                <?= strtoupper($user_rol) ?>
            </span>
        </div>
        <div class="flex items-center space-x-4">
            <span class="text-sm font-medium text-slate-300 hidden md:inline"><?= htmlspecialchars($_SESSION['actafl_nombre'] ?? 'Usuario') ?></span>
            <a href="../api/logout.php" class="bg-red-500/20 text-red-300 hover:bg-red-600 hover:text-white px-3 py-1.5 rounded-xl text-xs font-bold transition-all">
                <i class="fas fa-power-off mr-1"></i> Salir
            </a>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto p-4 md:p-6 space-y-8">

        <!-- Encabezado y Acción Principal -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <div>
                <h2 class="text-2xl font-bold text-slate-800 tracking-tight">Gestión de Obras & Parametrización</h2>
                <p class="text-slate-500 text-sm mt-1">Crea proyectos y personaliza los formularios de inspección para el personal de campo.</p>
            </div>
            <button onclick="abrirModalProyecto()" class="bg-blue-600 text-white px-5 py-3 rounded-xl font-bold shadow-lg shadow-blue-200 hover:bg-blue-700 transition-all flex items-center justify-center">
                <i class="fas fa-plus mr-2"></i> Crear Nuevo Proyecto
            </button>
        </div>

        <!-- Grilla de Proyectos Existentes -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach($proyectos as$p): ?>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 flex flex-col justify-between hover:border-blue-400 transition-all">
                <div>
                    <div class="flex justify-between items-start mb-3">
                        <span class="text-[10px] bg-blue-100 text-blue-700 px-2.5 py-1 rounded-lg font-bold uppercase tracking-wider">
                            ID #<?= $p['id'] ?>
                        </span>
                        <span class="text-xs font-bold <?= $p['activo'] ? 'text-emerald-600' : 'text-slate-400' ?>">
                            <?= $p['activo'] ? '● Activo' : '○ Inactivo' ?>
                        </span>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800 leading-snug mb-1"><?= htmlspecialchars($p['nombre']) ?></h3>
                    <p class="text-xs text-slate-500 mb-4"><?= htmlspecialchars($p['descripcion'] ?? 'Sin descripción registrada') ?></p>
                    
                    <div class="text-xs text-slate-600 space-y-1 border-t pt-3 border-slate-100">
                        <p><i class="fas fa-user-tie mr-2 text-slate-400"></i><b>Coordinador:</b> <?= htmlspecialchars($p['coordinador'] ?? 'Sin asignar') ?></p>
                        <p><i class="fas fa-calendar-alt mr-2 text-slate-400"></i><b>Creado:</b> <?= date('d/m/Y', strtotime($p['creado_en'])) ?></p>
                    </div>
                </div>

                <div class="mt-6 flex space-x-2 border-t pt-4 border-slate-100">
                    <button onclick="abrirParametrizador(<?= $p['id'] ?>, '<?= htmlspecialchars($p['nombre'], ENT_QUOTES) ?>')" 
                            class="flex-1 bg-slate-900 text-white text-xs py-2.5 rounded-xl font-bold hover:bg-blue-600 transition-all flex items-center justify-center">
                        <i class="fas fa-sliders mr-1.5"></i> Formulario
                    </button>
                    <button onclick="abrirAsignarInspectores(<?= $p['id'] ?>)" 
                            class="p-2.5 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 transition-all" title="Asignar Inspectores">
                        <i class="fas fa-users-gear"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if(empty($proyectos)): ?>
            <div class="col-span-full bg-white p-12 text-center rounded-2xl border border-dashed border-slate-300">
                <i class="fas fa-folder-open text-4xl text-slate-300 mb-3 block"></i>
                <p class="text-slate-500 font-medium">No hay proyectos creados aún. Registra el primero para comenzar a parametrizar.</p>
            </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Modal 1: Crear Proyecto -->
    <div id="modalProyecto" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-2xl space-y-4">
            <h3 class="text-xl font-bold text-slate-800">Nuevo Proyecto de Inspección</h3>
            <form action="../api/proyectos.php" method="POST" class="space-y-4">
                <input type="hidden" name="accion" value="crear_proyecto">
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Nombre de la Obra / Proyecto</label>
                    <input type="text" name="nombre" required placeholder="Ej: Proyecto Edificio San Jorge" class="w-full p-3 border border-slate-200 rounded-xl bg-slate-50 outline-none focus:ring-2 focus:ring-blue-500 font-semibold text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Descripción</label>
                    <textarea name="descripcion" rows="2" placeholder="Detalles de la obra civil..." class="w-full p-3 border border-slate-200 rounded-xl bg-slate-50 outline-none focus:ring-2 focus:ring-blue-500 text-sm"></textarea>
                </div>
                <div class="flex space-x-3 pt-2">
                    <button type="button" onclick="cerrarModal('modalProyecto')" class="flex-1 py-3 text-slate-400 font-bold text-sm">Cancelar</button>
                    <button type="submit" class="flex-1 bg-blue-600 text-white py-3 rounded-xl font-bold shadow-lg shadow-blue-100 hover:bg-blue-700 text-sm">Guardar Proyecto</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 2: Parametrizador Dinámico de Formulario por Proyecto -->
    <div id="modalParametrizador" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-2xl p-6 w-full max-w-2xl shadow-2xl space-y-6 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center border-b pb-3">
                <div>
                    <h3 id="tituloParametrizador" class="text-lg font-bold text-slate-800">Parametrizar Formulario</h3>
                    <p class="text-xs text-slate-400">Define los campos que completarán los inspectores en la PWA.</p>
                </div>
                <button onclick="cerrarModal('modalParametrizador')" class="text-slate-400 hover:text-slate-600 text-lg">×</button>
            </div>

            <!-- Formulario para agregar un campo dinámico -->
            <form id="formNuevoCampo" onsubmit="agregarCampoParametrizado(event)" class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-3">
                <input type="hidden" id="param_proyecto_id">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-500 mb-1">Nombre del Campo (Label)</label>
                        <input type="text" id="campo_label" required placeholder="Ej: Estado de Fachada" class="w-full p-2.5 bg-white border border-slate-200 rounded-lg text-sm font-semibold">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Tipo de Entrada</label>
                        <select id="campo_tipo" class="w-full p-2.5 bg-white border border-slate-200 rounded-lg text-sm font-semibold">
                            <option value="texto">Texto Libre</option>
                            <option value="numero">Número</option>
                            <option value="select">Lista de Selección</option>
                            <option value="checkbox">Casilla (Sí / No)</option>
                            <option value="foto">Fotografía</option>
                            <option value="firma">Firma Digital</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="bg-slate-900 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-blue-600 transition-all">
                        + Agregar Campo
                    </button>
                </div>
            </form>

            <!-- Lista de Campos Configurados -->
            <div>
                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Campos Actuales de la Plantilla</h4>
                <div id="contenedorCamposFormulario" class="space-y-2">
                    <p class="text-xs text-slate-400 italic text-center py-4">Cargando plantilla del proyecto...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function abrirModal(id) { document.getElementById(id).classList.remove('hidden'); }
        function cerrarModal(id) { document.getElementById(id).classList.add('hidden'); }
        function abrirModalProyecto() { abrirModal('modalProyecto'); }

        function abrirParametrizador(proyectoId, nombreProyecto) {
            document.getElementById('param_proyecto_id').value = proyectoId;
            document.getElementById('tituloParametrizador').innerText = 'Parametrizar: ' + nombreProyecto;
            cargarCamposProyecto(proyectoId);
            abrirModal('modalParametrizador');
        }

        async function cargarCamposProyecto(proyectoId) {
            const cont = document.getElementById('contenedorCamposFormulario');
            try {
                const res = await fetch(`../api/proyectos.php?accion=listar_campos&proyecto_id=${proyectoId}`);
                const campos = await res.json();
                if (campos.length === 0) {
                    cont.innerHTML = '<p class="text-xs text-slate-400 italic text-center py-4">Este proyecto usa la plantilla estándar por defecto.</p>';
                    return;
                }
                cont.innerHTML = '';
                campos.forEach(c => {
                    cont.innerHTML += `
                        <div class="flex items-center justify-between bg-slate-50 p-3 rounded-xl border border-slate-200 text-sm">
                            <div>
                                <p class="font-bold text-slate-700">${c.label_campo}</p>
                                <span class="text-[10px] bg-blue-100 text-blue-700 font-bold px-2 py-0.5 rounded uppercase">${c.tipo_campo}</span>
                            </div>
                            <button onclick="eliminarCampo(${c.id}, ${proyectoId})" class="text-red-500 hover:text-red-700 text-xs font-bold p-1">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `;
                });
            } catch (e) {
                cont.innerHTML = '<p class="text-xs text-red-500 italic text-center">Error al cargar la plantilla.</p>';
            }
        }

        async function agregarCampoParametrizado(e) {
            e.preventDefault();
            const proyectoId = document.getElementById('param_proyecto_id').value;
            const payload = {
                accion: 'agregar_campo',
                proyecto_id: proyectoId,
                label_campo: document.getElementById('campo_label').value,
                tipo_campo: document.getElementById('campo_tipo').value
            };
            await fetch('../api/proyectos.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            document.getElementById('campo_label').value = '';
            cargarCamposProyecto(proyectoId);
        }

        async function eliminarCampo(campoId, proyectoId) {
            if (!confirm('¿Eliminar este campo del formulario?')) return;
            await fetch('../api/proyectos.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ accion: 'eliminar_campo', campo_id: campoId })
            });
            cargarCamposProyecto(proyectoId);
        }
    </script>
</body>
</html>