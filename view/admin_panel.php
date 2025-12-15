<?php
session_start();
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] != 2) {
    header('Location: ../index.php');
    exit;
}

require_once '../BBDD/conexion.php';

// --- FILTERS LOGIC ---

// 1. Staff Filters
$conditions_users = ["rol IN (1, 4, 5)"];
$params_users = [];

$f_user_name = trim($_GET['f_user_name'] ?? '');
$f_user_rol = $_GET['f_user_rol'] ?? '';
$f_user_status = $_GET['f_user_status'] ?? ''; // '', 'active', 'inactive'

if ($f_user_name !== '') {
    $conditions_users[] = "(username LIKE :u_name OR nombre LIKE :u_name OR apellido LIKE :u_name)";
    $params_users[':u_name'] = "%$f_user_name%";
}
if ($f_user_rol !== '') {
    $conditions_users[] = "rol = :u_rol";
    $params_users[':u_rol'] = $f_user_rol;
}
if ($f_user_status === 'active') {
    $conditions_users[] = "fecha_baja IS NULL";
} elseif ($f_user_status === 'inactive') {
    $conditions_users[] = "fecha_baja IS NOT NULL";
}

$sql_users = "SELECT id, username, nombre, apellido, email, rol, fecha_baja FROM users WHERE " . implode(' AND ', $conditions_users);
$stmt_users = $conn->prepare($sql_users);
$stmt_users->execute($params_users);
$staff_users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);


// 2. Room Filters
$conditions_salas = ["1=1"];
$params_salas = [];

$f_sala_name = trim($_GET['f_sala_name'] ?? '');

if ($f_sala_name !== '') {
    $conditions_salas[] = "nombre LIKE :s_name";
    $params_salas[':s_name'] = "%$f_sala_name%";
}

$sql_salas = "SELECT * FROM salas WHERE " . implode(' AND ', $conditions_salas);
$stmt_salas = $conn->prepare($sql_salas);
$stmt_salas->execute($params_salas);
$salas = $stmt_salas->fetchAll(PDO::FETCH_ASSOC);


// Map Roles to Names
$role_names = [
    1 => 'Camarero',
    4 => 'Mantenimiento',
    5 => 'Jefe de Sala'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Casa GMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">
    <!-- Header CSS is often embedded or separate, we'll assume header.php brings some or we style it ourselves -->
    <link rel="stylesheet" href="../css/panel_principal.css"> 
</head>
<body class="admin-body">
    <?php include '../header.php'; ?>

    <div class="main-content">
        
        <!-- Summary Types -->
        <!-- Summary Types -->
        <div class="row mb-4 g-4">
             <!-- Stats Cards similar to historico -->
             <div class="col-md-6">
                <div class="metric-card info">
                    <i class="fas fa-users metric-icon"></i>
                    <h2 class="metric-number"><?php echo count($staff_users); ?></h2>
                    <p class="metric-label">Empleados Activos</p>
                </div>
            </div>
            <div class="col-md-6">
                 <div class="metric-card">
                    <i class="fas fa-door-open metric-icon"></i>
                    <h2 class="metric-number"><?php echo count($salas); ?></h2>
                    <p class="metric-label">Salas Registradas</p>
                </div>
            </div>
            <!-- Quick Link to Reservas -->
            <div class="col-md-12">
                 <a href="reservas.php" style="text-decoration:none;">
                     <div class="metric-card info" style="border: 2px solid #5a9bd4; cursor:pointer;">
                        <i class="fas fa-calendar-days metric-icon"></i>
                        <h2 class="metric-number" style="font-size: 1.5rem;">GESTIONAR RESERVAS</h2>
                        <p class="metric-label">Ir al Panel de Reservas</p>
                    </div>
                 </a>
            </div>
        </div>

        <!-- Gestión de Personal -->
        <div class="glass-card">
            <div class="section-title">
                <i class="fas fa-users-gear text-primary" style="color: #c94736;"></i>
                Gestión de Personal
            </div>

            <!-- Staff Filter Form -->
            <form method="get" action="admin_panel.php" class="filter-form-inline" style="margin-bottom: 20px;">
                <!-- Preserve room filters if set -->
                <?php if (!empty($f_sala_name)): ?>
                    <input type="hidden" name="f_sala_name" value="<?php echo htmlspecialchars($f_sala_name); ?>">
                <?php endif; ?>

                <div class="row g-2">
                    <div class="col-md-3">
                        <label for="f_user_name" class="form-label-sm">Buscar por nombre o usuario</label>
                        <input type="text" name="f_user_name" id="f_user_name" class="form-control form-control-sm" placeholder="Ej: Juan" value="<?php echo htmlspecialchars($f_user_name); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="f_user_rol" class="form-label-sm">Filtrar por Rol</label>
                        <select name="f_user_rol" id="f_user_rol" class="form-select form-select-sm" style="width:100%; padding: 6px; border-radius:6px; border:1px solid #ccc;">
                            <option value="">Todos los Roles</option>
                            <option value="1" <?php echo $f_user_rol == '1' ? 'selected' : ''; ?>>Camarero</option>
                            <option value="5" <?php echo $f_user_rol == '5' ? 'selected' : ''; ?>>Jefe de Sala</option>
                            <option value="4" <?php echo $f_user_rol == '4' ? 'selected' : ''; ?>>Mantenimiento</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="f_user_status" class="form-label-sm">Filtrar por Estado</label>
                        <select name="f_user_status" id="f_user_status" class="form-select form-select-sm" style="width:100%; padding: 6px; border-radius:6px; border:1px solid #ccc;">
                            <option value="">Todos</option>
                            <option value="active" <?php echo $f_user_status == 'active' ? 'selected' : ''; ?>>Activos</option>
                            <option value="inactive" <?php echo $f_user_status == 'inactive' ? 'selected' : ''; ?>>Inactivos</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn-submit" style="padding: 6px 15px; font-size: 0.9rem;">Filtrar Personal</button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="futuristic-table">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($staff_users as $user): ?>
                        <tr class="<?php echo !empty($user['fecha_baja']) ? 'inactive-row' : ''; ?>">
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['nombre'] . ' ' . $user['apellido']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="badge badge-rol-<?php echo $user['rol']; ?>">
                                    <?php echo $role_names[$user['rol']] ?? 'Desconocido'; ?>
                                </span>
                            </td>
                            <td>
                                <?php if(empty($user['fecha_baja'])): ?>
                                    <span class="badge badge-success" style="background: #28a745; color: white;">Activo</span>
                                <?php else: ?>
                                    <span class="badge badge-danger" style="background: #dc3545; color: white;">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn-icon-wrapper edit-user-btn" 
                                        data-id="<?php echo $user['id']; ?>"
                                        data-user="<?php echo htmlspecialchars($user['username']); ?>"
                                        data-rol="<?php echo $user['rol']; ?>"
                                        title="Editar Rol">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                
                                <form action="../proc/admin_actions_proc.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="toggle_user_status">
                                    <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                    <?php if(empty($user['fecha_baja'])): ?>
                                        <button type="submit" class="btn-icon-wrapper" title="Desactivar Usuario" onclick="return confirm('¿Estás seguro de desactivar este usuario?');">
                                            <i class="fa-solid fa-user-slash" style="color: #dc3545;"></i>
                                        </button>
                                    <?php else: ?>
                                        <button type="submit" class="btn-icon-wrapper" title="Activar Usuario">
                                            <i class="fa-solid fa-user-check" style="color: #28a745;"></i>
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Gestión de Salas -->
        <div class="glass-card">
            <div class="section-title" style="justify-content: space-between;">
                <div style="display:flex; align-items:center; gap:10px;">
                    <i class="fas fa-door-open text-primary" style="color: #c94736;"></i>
                    Gestión de Salas
                </div>
                <button class="btn-submit" style="width: auto; padding: 8px 20px; font-size: 0.9rem;" onclick="openAddSalaModal()">
                    <i class="fas fa-plus"></i> Añadir Sala
                </button>
            </div>

            <!-- Room Filter Form -->
            <form method="get" action="admin_panel.php" class="filter-form-inline" style="margin-bottom: 20px;">
                <!-- Preserve staff filters if set -->
                <?php if (!empty($f_user_name)): ?> <input type="hidden" name="f_user_name" value="<?php echo htmlspecialchars($f_user_name); ?>"> <?php endif; ?>
                <?php if (!empty($f_user_rol)): ?> <input type="hidden" name="f_user_rol" value="<?php echo htmlspecialchars($f_user_rol); ?>"> <?php endif; ?>
                <?php if (!empty($f_user_status)): ?> <input type="hidden" name="f_user_status" value="<?php echo htmlspecialchars($f_user_status); ?>"> <?php endif; ?>

                <div class="row g-2">
                    <div class="col-md-9">
                        <label for="f_sala_name" class="form-label-sm">Buscar por nombre de sala</label>
                        <input type="text" name="f_sala_name" id="f_sala_name" class="form-control form-control-sm" placeholder="Ej: Terraza" value="<?php echo htmlspecialchars($f_sala_name); ?>">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn-submit" style="padding: 6px 15px; font-size: 0.9rem;">Filtrar Salas</button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="futuristic-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre Sala</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($salas as $sala): ?>
                        <tr>
                            <td>#<?php echo htmlspecialchars($sala['id']); ?></td>
                            <td><?php echo htmlspecialchars($sala['nombre']); ?></td>
                            <td>
                                <button class="btn-icon-wrapper edit-sala-btn" 
                                        data-id="<?php echo $sala['id']; ?>"
                                        data-nombre="<?php echo htmlspecialchars($sala['nombre']); ?>"
                                        title="Editar Sala">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                
                                <form action="../proc/admin_actions_proc.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="delete_sala">
                                    <input type="hidden" name="id" value="<?php echo $sala['id']; ?>">
                                    <button type="submit" class="btn-icon-wrapper" title="Eliminar Sala" onclick="return confirm('¡ATENCIÓN! Al borrar la sala se borrarán todas sus mesas automáticamente. ¿Estás seguro?');">
                                        <i class="fa-solid fa-trash" style="color: #dc3545;"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Editar Usuario -->
    <div id="modalUser" class="modal">
        <div class="modal-content glass-card">
            <span class="close-btn" onclick="closeModal('modalUser')">&times;</span>
            <h2>Editar Rol de Usuario</h2>
            <form action="../proc/admin_actions_proc.php" method="POST">
                <input type="hidden" name="action" value="edit_user">
                <input type="hidden" name="id" id="edit_user_id">
                
                <div class="form-group">
                    <label>Usuario</label>
                    <input type="text" id="edit_user_username" disabled class="input-disabled">
                </div>
                
                <div class="form-group">
                    <label>Rol</label>
                    <select name="rol" id="edit_user_rol" class="futuristic-input">
                        <option value="1">Camarero</option>
                        <option value="5">Jefe de Sala</option>
                        <option value="4">Mantenimiento</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-submit">Guardar Cambios</button>
            </form>
        </div>
    </div>

    <!-- Modal Editar Sala -->
    <div id="modalSala" class="modal">
        <div class="modal-content glass-card">
            <span class="close-btn" onclick="closeModal('modalSala')">&times;</span>
            <h2>Editar Sala</h2>
            <form action="../proc/admin_actions_proc.php" method="POST">
                <input type="hidden" name="action" value="edit_sala">
                <input type="hidden" name="id" id="edit_sala_id">
                
                <div class="form-group">
                    <label>Nombre de la Sala</label>
                    <input type="text" name="nombre" id="edit_sala_nombre" class="futuristic-input" required>
                </div>
                
                <button type="submit" class="btn-submit">Guardar Cambios</button>
            </form>
        </div>
    </div>

    <!-- Modal Añadir Sala -->
    <div id="modalAddSala" class="modal">
        <div class="modal-content glass-card">
            <span class="close-btn" onclick="closeModal('modalAddSala')">&times;</span>
            <h2>Añadir Nueva Sala</h2>
            <form action="../proc/admin_actions_proc.php" method="POST">
                <input type="hidden" name="action" value="create_sala">
                
                <div class="form-group">
                    <label>Nombre de la Sala</label>
                    <input type="text" name="nombre" class="futuristic-input" placeholder="Ej: Terraza Exterior" required>
                </div>
                
                <button type="submit" class="btn-submit">Crear Sala</button>
            </form>
        </div>
    </div>

    <script>
        // Modal Logic
        const modalUser = document.getElementById('modalUser');
        const modalSala = document.getElementById('modalSala');
        const modalAddSala = document.getElementById('modalAddSala');

        document.querySelectorAll('.edit-user-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('edit_user_id').value = btn.dataset.id;
                document.getElementById('edit_user_username').value = btn.dataset.user;
                document.getElementById('edit_user_rol').value = btn.dataset.rol;
                modalUser.style.display = 'flex';
            });
        });

        document.querySelectorAll('.edit-sala-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('edit_sala_id').value = btn.dataset.id;
                document.getElementById('edit_sala_nombre').value = btn.dataset.nombre;
                modalSala.style.display = 'flex';
            });
        });

        function openAddSalaModal() {
            modalAddSala.style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == modalUser) modalUser.style.display = 'none';
            if (event.target == modalSala) modalSala.style.display = 'none';
            if (event.target == modalAddSala) modalAddSala.style.display = 'none';
        }
    </script>
</body>
</html>
