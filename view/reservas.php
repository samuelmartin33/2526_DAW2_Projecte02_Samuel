<?php
session_start();
require_once '../bbdd/conexion.php';

// Validaciones de sesión
if (!isset($_SESSION['loginok'])) { header("Location: ../login.php"); exit(); }

$username = $_SESSION['username'];
$rol = $_SESSION['rol'] ?? 1;

// --- FILTROS ---
$id_sala_actual = $_GET['id_sala'] ?? 1;
$fecha_filtro = $_GET['fecha'] ?? date('Y-m-d');
$hora_filtro  = $_GET['hora'] ?? date('H:i');
// Timestamp objetivo para comprobar ocupación
$target_datetime = $fecha_filtro . ' ' . $hora_filtro . ':00';

// 1. Obtener Info de la Sala
$stmt = $conn->prepare("SELECT * FROM salas WHERE id = ?");
$stmt->execute([$id_sala_actual]);
$sala_info = $stmt->fetch(PDO::FETCH_ASSOC);

// Si la sala no existe, fallback a la 1
if (!$sala_info) {
    $id_sala_actual = 1;
    $stmt->execute([$id_sala_actual]);
    $sala_info = $stmt->fetch(PDO::FETCH_ASSOC);
}

// 2. Obtener lista de salas para el selector
$salas_lista = $conn->query("SELECT id, nombre FROM salas")->fetchAll(PDO::FETCH_ASSOC);

// 3. Obtener Mesas
$stmt_mesas = $conn->prepare("SELECT * FROM mesas WHERE id_sala = ?");
$stmt_mesas->execute([$id_sala_actual]);
$mesas = $stmt_mesas->fetchAll(PDO::FETCH_ASSOC);

// 4. Obtener Reservas que "ocupan" las mesas en ese momento
// Una mesa está "ocupada" si existe una reserva confirmada/pendiente que incluya el target_datetime
$sql_ocupadas = "SELECT r.*, u.nombre as nombre_cliente, u.apellido as apellido_cliente, u.username as user_cliente
                 FROM reservas r
                 JOIN users u ON r.id_usuario_reserva = u.id
                 WHERE r.id_mesa IN (SELECT id FROM mesas WHERE id_sala = ?)
                 AND r.estado IN (1, 2) -- Pendiente o Confirmada
                 AND r.fecha_inicio <= ? 
                 AND r.fecha_fin > ?";

$stmt_ocupadas = $conn->prepare($sql_ocupadas);
$stmt_ocupadas->execute([$id_sala_actual, $target_datetime, $target_datetime]);
$reservas_activas = $stmt_ocupadas->fetchAll(PDO::FETCH_ASSOC);

// Mapear reservas por id_mesa para acceso rápido
$mesas_ocupadas = [];
foreach ($reservas_activas as $res) {
    $mesas_ocupadas[$res['id_mesa']] = $res;
}

// 5. Obtener lista de clientes (para el modal de nueva reserva)
$stmt_clientes = $conn->query("SELECT id, nombre, apellido, username FROM users WHERE rol = 3 ORDER BY nombre");
$clientes = $stmt_clientes->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Reservas - <?= htmlspecialchars($sala_info['nombre']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/panel_principal.css">
    <link rel="stylesheet" href="../css/salas_general.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .sala-layout {
            position: relative; 
            width: 100%;
            height: 75vh; 
            background-image: url('<?php
                $nombre_img = strtolower(str_replace(' ', '', $sala_info['nombre'])) . '.png';
                $ruta_img = "../img/" . $nombre_img;
                echo file_exists($ruta_img) ? $ruta_img : "../img/fondo_panel_principal.png";
            ?>'); 
            background-size: 100% 100%;
            background-repeat: no-repeat;
            background-position: center;
            border: 5px solid #d1a785;
            overflow: hidden; 
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .mesa-container {
            position: absolute;
            width: 100px; /* Un poco más pequeñas para esta vista quizás? o igual */
            text-align: center;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .mesa-container:hover {
            transform: scale(1.05);
        }

        .mesa-img {
            width: 100%;
            transition: filter 0.3s;
        }
        
        /* Estado por defecto: Libre (Verde/Normal) */
        
        /* Estado Ocupada (Por Reserva en la fecha seleccionada) */
        .mesa-reservada-visual .mesa-img {
            filter: sepia(1) hue-rotate(-50deg) saturate(3) drop-shadow(0 0 5px red);
        }
        
        .capacidad-badge {
            position: absolute;
            top: -10px;
            right: -10px;
            background: #0d6efd;
            color: white;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .nombre-mesa {
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 12px;
            display: inline-block;
            margin-top: -5px;
        }

        .filter-bar {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php require_once '../header.php'; ?>

    <div class="container-fluid mt-4">
        
        <!-- AVISO DE MENSAJES -->
        <?php if(isset($_GET['msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                    if($_GET['msg'] == 'reserva_ok') echo "Reserva creada correctamente.";
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_GET['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- BARRA DE FILTROS -->
        <div class="filter-bar">
            <form action="" method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-bold"><i class="fa-solid fa-map"></i> Sala</label>
                    <select name="id_sala" class="form-select">
                        <?php foreach($salas_lista as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= $s['id'] == $id_sala_actual ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold"><i class="fa-solid fa-calendar"></i> Fecha</label>
                    <input type="date" name="fecha" class="form-control" value="<?= $fecha_filtro ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold"><i class="fa-solid fa-clock"></i> Hora Visualización</label>
                    <input type="time" name="hora" class="form-control" value="<?= $hora_filtro ?>">
                </div>
                <div class="col-md-2">
                     <button type="submit" class="btn btn-primary w-100 fw-bold">
                        <i class="fa-solid fa-filter"></i> Filtrar
                     </button>
                </div>
                <div class="col-md-2 text-end">
                    <span class="text-muted small">
                        Viendo: <strong><?= $fecha_filtro ?> <?= $hora_filtro ?></strong>
                    </span>
                    <br>
                    <a href="reservas.php" class="btn btn-outline-secondary btn-sm mt-1">
                        <i class="fa-solid fa-rotate-right"></i> Ir a Ahora
                    </a>
                </div>
            </form>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0"><?= htmlspecialchars($sala_info['nombre']) ?></h4>
                        <span><i class="fa-solid fa-circle-info"></i> Haz clic en una mesa para reservar o ver info</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="sala-layout" id="lienzo">
                            <?php foreach ($mesas as $mesa): ?>
                                <?php 
                                    $id = $mesa['id'];
                                    $ocupada = isset($mesas_ocupadas[$id]);
                                    $clase_extra = $ocupada ? 'mesa-reservada-visual' : '';
                                    
                                    // Datos para JS
                                    $datos_reserva = $ocupada ? htmlspecialchars(json_encode($mesas_ocupadas[$id])) : 'null';
                                ?>
                                <div class="mesa-container <?= $clase_extra ?>" 
                                     style="left: <?= $mesa['pos_x'] ?? 50 ?>%; top: <?= $mesa['pos_y'] ?? 50 ?>%;"
                                     onclick='handleClickMesa(<?= $id ?>, "<?= htmlspecialchars($mesa['nombre']) ?>", <?= $mesa['sillas'] ?>, <?= $datos_reserva ?>)'>
                                    
                                    <div class="capacidad-badge"><?= $mesa['sillas'] ?></div>
                                    <img src="../img/mesa1.png" class="mesa-img">
                                    <div class="nombre-mesa"><?= htmlspecialchars($mesa['nombre']) ?></div>
                                    
                                    <?php if($ocupada): ?>
                                        <div style="position:absolute; top:30%; left:0; width:100%; text-align:center; color:white; font-weight:bold; text-shadow: 1px 1px 2px black; pointer-events:none;">
                                            <i class="fa-solid fa-lock"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL DETALLE RESERVA -->
    <div class="modal fade" id="modalDetalleReserva" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fa-solid fa-file-contract"></i> Mesa Reservada</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3 row">
                        <label class="col-sm-4 col-form-label fw-bold">Mesa:</label>
                        <div class="col-sm-8">
                            <input type="text" readonly class="form-control-plaintext" id="info_mesa_nombre">
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label class="col-sm-4 col-form-label fw-bold">Cliente:</label>
                        <div class="col-sm-8">
                            <input type="text" readonly class="form-control-plaintext" id="info_cliente">
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label class="col-sm-4 col-form-label fw-bold">Horario:</label>
                        <div class="col-sm-8">
                            <input type="text" readonly class="form-control-plaintext" id="info_horario">
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label class="col-sm-4 col-form-label fw-bold">Comensales:</label>
                        <div class="col-sm-8">
                            <input type="text" readonly class="form-control-plaintext" id="info_comensales">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <!-- Aquí se podría añadir botón para cancelar reserva si se desea -->
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL NUEVA RESERVA -->
    <div class="modal fade" id="modalNuevaReserva" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fa-solid fa-plus-circle"></i> Nueva Reserva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="../proc/reservar_mesa.php" method="POST">
                    <div class="modal-body">
                        <!-- Redirect Back -->
                        <input type="hidden" name="redirect_url" value="reservas.php?id_sala=<?= $id_sala_actual ?>&fecha=<?= $fecha_filtro ?>&hora=<?= $hora_filtro ?>">
                        
                        <input type="hidden" name="id_mesa" id="new_id_mesa">
                        <input type="hidden" name="id_sala" value="<?= $id_sala_actual ?>">
                        
                        <p class="mb-3">Creando reserva para la mesa <strong><span id="new_mesa_nombre_display"></span></strong>.</p>
                        
                        <div class="mb-3">
                            <label class="form-label">Cliente:</label>
                            <select class="form-select" name="id_cliente" required>
                                <option value="" disabled selected>Seleccione Cliente</option>
                                <?php foreach ($clientes as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre'] . ' ' . $c['apellido']) ?> (<?= htmlspecialchars($c['username']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Desde:</label>
                                <!-- Pre-fill with current filter time -->
                                <input type="datetime-local" class="form-control" name="fecha_inicio" id="new_fecha_inicio" required onchange="updateFechaFin()">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Duración:</label>
                                <select class="form-select" id="new_duracion" onchange="updateFechaFin()">
                                    <option value="90">1h 30m (Mínimo)</option>
                                    <option value="180">3h 00m</option>
                                    <option value="270">4h 30m</option>
                                    <option value="360">6h 00m</option>
                                </select>
                                <input type="hidden" name="fecha_fin" id="new_fecha_fin">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Comensales:</label>
                            <input type="number" class="form-control" name="num_comensales" id="new_num_comensales" min="1" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Confirmar Reserva</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const modalDetalle = new bootstrap.Modal(document.getElementById('modalDetalleReserva'));
        const modalNueva = new bootstrap.Modal(document.getElementById('modalNuevaReserva'));
        
        // Variables PHP pasadas a JS para usar en los defaults
        const fechaFiltro = "<?= $fecha_filtro ?>";
        const horaFiltro = "<?= $hora_filtro ?>";

        function handleClickMesa(idMesa, nombreMesa, capacidad, reservaData) {
            if (reservaData) {
                // MESA OCUPADA/RESERVADA
                document.getElementById('info_mesa_nombre').value = nombreMesa;
                document.getElementById('info_cliente').value = reservaData.nombre_cliente + ' ' + reservaData.apellido_cliente;
                document.getElementById('info_horario').value = formatFecha(reservaData.fecha_inicio) + ' - ' + formatFecha(reservaData.fecha_fin);
                document.getElementById('info_comensales').value = reservaData.num_comensales;
                
                modalDetalle.show();
            } else {
                // MESA LIBRE
                // Check permissions (Passed from PHP)
                const userRole = <?= $rol ?>;
                if (![1, 5].includes(userRole)) {
                    // Optional: Show alert
                    alert("Solo camareros y jefes de sala pueden crear reservas.");
                    return;
                }

                document.getElementById('new_id_mesa').value = idMesa;
                document.getElementById('new_mesa_nombre_display').innerText = nombreMesa;
                document.getElementById('new_num_comensales').max = capacidad;
                document.getElementById('new_num_comensales').value = '';
                
                // Pre-rellenar fechas basándonos en el filtro
                // Construir fecha base
                const baseStr = fechaFiltro + 'T' + horaFiltro;
                const baseDate = new Date(baseStr);
                
                // Ajustar timezone
                // (date strings use local time in input datetime-local usually)
                document.getElementById('new_fecha_inicio').value = baseStr;
                
                // Reset duración to default (90 min)
                document.getElementById('new_duracion').value = "90";
                
                // Calculate initial End Date
                updateFechaFin();
                
                modalNueva.show();
            }
        }
        
        function updateFechaFin() {
            const startInput = document.getElementById('new_fecha_inicio');
            const duracionSelect = document.getElementById('new_duracion');
            const endInput = document.getElementById('new_fecha_fin');
            
            if(!startInput.value) return;
            
            const startDate = new Date(startInput.value);
            const minutes = parseInt(duracionSelect.value);
            
            const endDate = new Date(startDate.getTime() + minutes * 60000);
            
            endInput.value = toIsoStringLocal(endDate);
        }
        
        function formatFecha(fechastr) {
            // Simple split para mostrar clean
            return fechastr; 
        }

        function toIsoStringLocal(d) {
            const pad = (n) => n < 10 ? '0' + n : n;
            return d.getFullYear() + '-' + 
                   pad(d.getMonth()+1) + '-' + 
                   pad(d.getDate()) + 'T' + 
                   pad(d.getHours()) + ':' + 
                   pad(d.getMinutes());
        }
    </script>
</body>
</html>
