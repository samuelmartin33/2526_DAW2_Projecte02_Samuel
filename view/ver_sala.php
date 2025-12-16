<?php
session_start();
require_once '../bbdd/conexion.php';

// Validaciones de sesión estándar de tu proyecto
if (!isset($_SESSION['loginok'])) { header("Location: ../login.php"); exit(); }

$username = $_SESSION['username'];
$rol = $_SESSION['rol'] ?? 1;

// Obtener ID de la sala de la URL (si no hay, por defecto la 1)
$id_sala_actual = $_GET['id_sala'] ?? 1;

// 1. Obtener Info de la Sala (Nombre, fondo si lo tuvieras en BD, etc)
$stmt = $conn->prepare("SELECT * FROM salas WHERE id = ?");
$stmt->execute([$id_sala_actual]);
$sala_info = $stmt->fetch(PDO::FETCH_ASSOC);

// 2. Obtener Mesas de esta sala
$stmt_mesas = $conn->prepare("SELECT * FROM mesas WHERE id_sala = ?");
$stmt_mesas->execute([$id_sala_actual]);
$mesas = $stmt_mesas->fetchAll(PDO::FETCH_ASSOC);

// 3. Obtener lista de salas para el menú lateral
$salas_nav = $conn->query("SELECT id, nombre FROM salas")->fetchAll(PDO::FETCH_ASSOC);

// 4. Obtener lista de clientes para reserva
$stmt_clientes = $conn->query("SELECT id, nombre, apellido, username FROM users WHERE rol = 3 ORDER BY nombre");
$clientes = $stmt_clientes->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($sala_info['nombre']) ?> - Editor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/panel_principal.css">
    <link rel="stylesheet" href="../css/salas_general.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        /* Estilos específicos para el modo edición */
        .sala-layout {
            /* IMPORTANTE: Posicionamiento relativo para que las mesas (absolutas) se muevan dentro */
            position: relative; 
            width: 100%;
            height: 80vh; /* Altura fija para el área de trabajo */
            background-image: url('<?php
                // Lógica para asignar imagen de fondo
                // PRIORIDAD 1: Imagen definida en BBDD (subida por Admin)
                if (!empty($sala_info['imagen'])) {
                    echo "../" . $sala_info['imagen'];
                } else {
                    // PRIORIDAD 2: Fallback antiguo
                    $nombre_img = strtolower(str_replace(' ', '', $sala_info['nombre'])) . '.png';
                    $ruta_img = "../img/" . $nombre_img;
                    
                    if (file_exists($ruta_img)) {
                        echo $ruta_img;
                    } else {
                        echo "../img/fondo_panel_principal.png";
                    }
                }
            ?>'); /* Fondo dinámico */
            background-size: 100% 100%;
            background-repeat: no-repeat;
            background-position: center;
            border: 5px solid #d1a785;
            overflow: hidden; 
        }

        .mesa-container {
            position: absolute; /* Se posiciona según top/left */
            width: 120px; /* Ancho base */
            text-align: center;
            cursor: pointer;
            /* Transición suave solo si NO estamos arrastrando (se controla con JS) */
        }

        .mesa-img {
            width: 100%;
            pointer-events: none; /* Evita arrastrar la imagen fantasma del navegador */
        }

        /* Estilos para cuando activamos el "Modo Edición" */
        .modo-edicion .mesa-container {
            border: 2px dashed #ce4535; /* Borde rojo discontinuo */
            background: rgba(255, 255, 255, 0.3);
            cursor: move; /* Cursor de movimiento */
            z-index: 10;
        }

        .btn-borrar {
            display: none; /* Oculto por defecto */
            position: absolute;
            top: -10px;
            right: -10px;
            background: red;
            color: white;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            text-align: center;
            line-height: 25px;
            text-decoration: none;
            font-weight: bold;
            font-size: 12px;
            z-index: 20;
        }

        .modo-edicion .btn-borrar {
            display: block; /* Visible solo en edición */
        }

        /* Estilo para mesas ocupadas (Rojo) */
        .mesa-ocupada .mesa-img {
            filter: sepia(1) hue-rotate(-50deg) saturate(3) drop-shadow(0 0 5px red);
            /* Esto torna la imagen rojiza. Ajusta según tus imágenes originales */
        }
        .mesa-ocupada .badge {
            background-color: #dc3545 !important; /* Badge rojo */
        }

        /* Capacidad Card Styles */
        .capacidad-card {
            position: absolute;
            top: -25px; /* Encima de la mesa */
            left: 50%;
            transform: translateX(-50%);
            background: white;
            border: 1px solid #ccc;
            border-radius: 10px;
            padding: 2px 8px;
            font-size: 12px;
            font-weight: bold;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            white-space: nowrap;
            z-index: 25;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .capacidad-controls {
            display: none; /* Oculto en modo vista */
        }

        .modo-edicion .capacidad-controls {
            display: inline-flex; /* Visible en modo edición */
            gap: 2px;
        }

        .btn-capacidad {
            width: 18px;
            height: 18px;
            line-height: 16px;
            text-align: center;
            font-size: 12px;
            padding: 0;
            border-radius: 50%;
            border: none;
            cursor: pointer;
        }
        .btn-mas { background: #28a745; color: white; }
        .btn-menos { background: #dc3545; color: white; }
    </style>
</head>
<body>
    
    <?php require_once '../header.php'; ?>

    <div class="container-fluid mt-3">
        <div class="row">
            
            <div class="col-md-2 d-none d-md-block bg-danger p-3 text-white" style="min-height: 80vh;">
                <h4>Salas</h4>
                <?php foreach ($salas_nav as $s): ?>
                    <a href="ver_sala.php?id_sala=<?= $s['id'] ?>" class="d-block text-white text-decoration-none p-2 <?= $s['id'] == $id_sala_actual ? 'fw-bold bg-white text-danger rounded' : '' ?>">
                        <?= $s['nombre'] ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="col-md-10">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2><?= htmlspecialchars($sala_info['nombre']) ?></h2>
                    
                    <div class="btn-group">
                        <button type="button" class="btn btn-warning" id="btn-activar-edicion" onclick="toggleEdicion()">
                            <i class="fa-solid fa-pen-to-square"></i> Editar Distribución
                        </button>
                        
                        <button type="button" class="btn btn-success" id="btn-anadir" style="display:none;" onclick="anadirMesaVisual()">
                            <i class="fa-solid fa-plus"></i> Añadir Mesa
                        </button>
                    </div>
                </div>

                <form action="../proc/guardar_posicion.php" method="POST" id="form-distribucion">
                    <input type="hidden" name="id_sala" value="<?= $id_sala_actual ?>">
                    
                    <button type="submit" class="btn btn-primary w-100 mb-2" id="btn-guardar" style="display:none;">
                        <i class="fa-solid fa-save"></i> GUARDAR CAMBIOS DE POSICIÓN
                    </button>

                    <div class="sala-layout" id="lienzo">
                        
                        <?php foreach ($mesas as $mesa): ?>
                            <?php 
                                $clase_ocupada = ($mesa['estado'] == 2) ? 'mesa-ocupada' : ''; 
                            ?>
                            <div class="mesa-container <?= $clase_ocupada ?>" 
                                 id="mesa-<?= $mesa['id'] ?>"
                                 style="left: <?= $mesa['pos_x'] ?? 50 ?>%; top: <?= $mesa['pos_y'] ?? 50 ?>%;"
                                 onmousedown="iniciarArrastre(event, this)"
                                 onclick="clickMesa(<?= $mesa['id'] ?>, <?= $mesa['estado'] ?>, <?= $mesa['sillas'] ?>, '<?= htmlspecialchars($mesa['nombre']) ?>')">
                                
                                <input type="hidden" name="posiciones[<?= $mesa['id'] ?>][x]" value="<?= $mesa['pos_x']         ?? 50 ?>" class="input-x">
                                <input type="hidden" name="posiciones[<?= $mesa['id'] ?>][y]" value="<?= $mesa['pos_y'] ?? 50 ?>" class="input-y">

                                <!-- Card Capacidad -->
                                <div class="capacidad-card" onclick="event.stopPropagation()" onmousedown="event.stopPropagation()">
                                    <i class="fa-solid fa-chair"></i> 
                                    <span class="display-sillas"><?= $mesa['sillas'] ?></span>
                                    <div class="capacidad-controls">
                                        <button type="button" class="btn-capacidad btn-menos" onclick="modificarSillas(this, -1)">-</button>
                                        <button type="button" class="btn-capacidad btn-mas" onclick="modificarSillas(this, 1)">+</button>
                                    </div>
                                    <input type="hidden" name="posiciones[<?= $mesa['id'] ?>][sillas]" value="<?= $mesa['sillas'] ?>" class="input-sillas">
                                </div>

                                <a href="../proc/eliminar_mesa.php?id_mesa=<?= $mesa['id'] ?>&id_sala=<?= $id_sala_actual ?>" 
                                   class="btn-borrar" 
                                   onclick="return confirm('¿Borrar esta mesa?')">X</a>

                                <img src="../img/mesa1.png" class="mesa-img">
                                <span class="badge bg-dark"><?= htmlspecialchars($mesa['nombre']) ?></span>
                            </div>
                        <?php endforeach; ?>

                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL ASIGNAR MESA -->
    <div class="modal fade" id="modalAsignar" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalAsignarTitulo">Asignar Mesa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="../proc/ocupar_mesa.php" method="POST" id="asignar-mesa-form">
                    <div class="modal-body">
                        <input type="hidden" name="id_mesa" id="asignar_id_mesa">
                        <input type="hidden" name="id_sala" value="<?= $id_sala_actual ?>">
                        
                        <div class="mb-3">
                            <label for="num-comensales" class="form-label">Número de comensales:</label>
                            <input type="number" class="form-control text-center fs-4" id="num-comensales" name="num_comensales" min="1">
                            <input type="hidden" id="max-sillas" value="">
                            <div class="error" style="display:none; color:red; margin-top:10px;"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="btn-asignar">Confirmar Asignación</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL LIBERAR MESA -->
    <div class="modal fade" id="modalLiberar" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="modalLiberarTitulo">Liberar Mesa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="../proc/liberar_mesa.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="id_mesa" id="liberar_id_mesa">
                        <input type="hidden" name="id_sala" value="<?= $id_sala_actual ?>">
                        
                        <p class="text-center fs-5">¿Seguro que quieres liberar esta mesa?</p>
                        <p class="text-center text-muted small">La ocupación se registrará como finalizada.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Sí, liberar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL RESERVAR MESA -->
    <div class="modal fade" id="modalReservar" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="modalReservarTitulo"><i class="fa-solid fa-calendar-plus"></i> Reservar Mesa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formReservarSala" action="../proc/reservar_mesa.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="id_mesa" id="reservar_id_mesa">
                        <input type="hidden" name="id_sala" value="<?= $id_sala_actual ?>">
                        
                        <div class="mb-3">
                            <label for="reserva_cliente" class="form-label">Cliente:</label>
                            <select class="form-select" name="id_cliente" id="reserva_cliente">
                                <option value="" disabled selected>Seleccione Cliente</option>
                                <?php foreach ($clientes as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre'] . ' ' . $c['apellido']) ?> (<?= htmlspecialchars($c['username']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label for="fecha_inicio" class="form-label">Inicio:</label>
                                <input type="datetime-local" class="form-control" name="fecha_inicio" id="fecha_inicio">
                            </div>
                            <div class="col-6 mb-3">
                                <label for="fecha_fin" class="form-label">Fin:</label>
                                <input type="datetime-local" class="form-control" name="fecha_fin" id="fecha_fin">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="reserva_comensales" class="form-label">Número de comensales:</label>
                            <input type="number" class="form-control text-center fs-4" id="reserva_comensales" name="num_comensales" min="1">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">Confirmar Reserva</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- MODAL OPCIONES (OCUPAR/RESERVAR) -->
     <div class="modal fade" id="modalOpciones" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Acciones</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body d-grid gap-2">
                    <button type="button" class="btn btn-primary btn-lg" onclick="abrirModalAsignar()">
                        <i class="fa-solid fa-utensils"></i> Ocupar Ahora
                    </button>
                    <!-- Botón Reservar eliminado por solicitud del usuario (solo desde Reservas) -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/validar_asignacion.js"></script>
    <script src="../js/validar_reservas_forms.js"></script>
    <!-- <script src="../js/alert_asignar.js"></script> Eliminado para unificar lógica -->
    <script>
        let editando = false;
        let elementoArrastrado = null;
        let offsetX = 0;
        let offsetY = 0;
        let contadorNuevas = 0;

        function toggleEdicion() {
            editando = !editando;
            const lienzo = document.getElementById('lienzo');
            const btnAnadir = document.getElementById('btn-anadir');
            const btnGuardar = document.getElementById('btn-guardar');
            const btnActivar = document.getElementById('btn-activar-edicion');

            if (editando) {
                lienzo.classList.add('modo-edicion');
                btnAnadir.style.display = 'inline-block';
                btnGuardar.style.display = 'block';
                btnActivar.classList.replace('btn-warning', 'btn-secondary');
                btnActivar.innerText = 'Cancelar Edición';
            } else {
                lienzo.classList.remove('modo-edicion');
                btnAnadir.style.display = 'none';
                btnGuardar.style.display = 'none';
                btnActivar.classList.replace('btn-secondary', 'btn-warning');
                btnActivar.innerHTML = '<i class="fa-solid fa-pen-to-square"></i> Editar Distribución';
                // Recargar para deshacer cambios no guardados
                location.reload();
            }
        }

        function anadirMesaVisual() {
            contadorNuevas++;
            const lienzo = document.getElementById('lienzo');

            // Crear div contenedor
            const div = document.createElement('div');
            div.className = 'mesa-container modo-edicion'; // Ya nace en modo edición
            div.style.left = '45%';
            div.style.top = '45%';
            div.setAttribute('onmousedown', 'iniciarArrastre(event, this)');
            
            // HTML interno
            div.innerHTML = `
                <input type="hidden" name="nuevas_mesas[${contadorNuevas}][x]" value="45" class="input-x">
                <input type="hidden" name="nuevas_mesas[${contadorNuevas}][y]" value="45" class="input-y">

                <div class="capacidad-card" onclick="event.stopPropagation()" onmousedown="event.stopPropagation()">
                    <i class="fa-solid fa-chair"></i> 
                    <span class="display-sillas">4</span>
                    <div class="capacidad-controls">
                        <button type="button" class="btn-capacidad btn-menos" onclick="modificarSillas(this, -1)">-</button>
                        <button type="button" class="btn-capacidad btn-mas" onclick="modificarSillas(this, 1)">+</button>
                    </div>
                    <input type="hidden" name="nuevas_mesas[${contadorNuevas}][sillas]" value="4" class="input-sillas">
                </div>

                <button type="button" class="btn-borrar" onclick="this.parentElement.remove()">X</button>

                <img src="../img/mesa1.png" class="mesa-img">
                <input type="text" name="nuevas_mesas[${contadorNuevas}][nombre]" value="Nueva ${contadorNuevas}" class="form-control form-control-sm mt-1 text-center font-weight-bold" style="background: rgba(255,255,255,0.9);">
            `;

            lienzo.appendChild(div);
        }

        function iniciarArrastre(e, elemento) {
            if (!editando) return; // Si no estamos editando, no hace nada

            // IMPORTANTE: Si hacemos click en el input de texto, NO iniciar arrastre
            if (e.target.tagName === 'INPUT') return;

            e.preventDefault(); // Evitar selección de texto
            elementoArrastrado = elemento;
            
            // Calculamos dónde hemos pinchado dentro de la mesa para que no "salte"
            const rect = elemento.getBoundingClientRect();
            offsetX = e.clientX - rect.left;
            offsetY = e.clientY - rect.top;

            // Añadimos eventos globales para mover
            document.addEventListener('mousemove', moverElemento);
            document.addEventListener('mouseup', soltarElemento);
        }

        function moverElemento(e) {
            if (!elementoArrastrado) return;

            const lienzo = document.getElementById('lienzo');
            const rectLienzo = lienzo.getBoundingClientRect();

            // Calculamos nueva posición en píxeles relativa al lienzo
            let x = e.clientX - rectLienzo.left - offsetX;
            let y = e.clientY - rectLienzo.top - offsetY;

            // Convertimos a porcentajes (para responsive)
            let porcentajeX = (x / rectLienzo.width) * 100;
            let porcentajeY = (y / rectLienzo.height) * 100;

            // Límites para que no se salga (0% a 90% aprox)
            porcentajeX = Math.max(0, Math.min(90, porcentajeX));
            porcentajeY = Math.max(0, Math.min(85, porcentajeY));

            // Aplicamos visualmente
            elementoArrastrado.style.left = porcentajeX + '%';
            elementoArrastrado.style.top = porcentajeY + '%';

            // ACTUALIZAMOS LOS INPUTS OCULTOS
            elementoArrastrado.querySelector('.input-x').value = porcentajeX;
            elementoArrastrado.querySelector('.input-y').value = porcentajeY;
        }

        function soltarElemento() {
            elementoArrastrado = null;
            document.removeEventListener('mousemove', moverElemento);
            document.removeEventListener('mouseup', soltarElemento);
        }

        const modalAsignar = new bootstrap.Modal(document.getElementById('modalAsignar'), { backdrop: 'static', keyboard: false });
        const modalLiberar = new bootstrap.Modal(document.getElementById('modalLiberar'), { backdrop: 'static', keyboard: false });
        const modalReservar = new bootstrap.Modal(document.getElementById('modalReservar'), { backdrop: 'static', keyboard: false });
        const modalOpciones = new bootstrap.Modal(document.getElementById('modalOpciones'), { backdrop: 'static', keyboard: false });

        let selectedMesaId = null;
        let selectedMesaCapacidad = 0;
        let selectedMesaNombre = '';

        function clickMesa(idMesa, estado, capacidad, nombreMesa) {
            if (editando) return; // Si estamos editando, NO navegamos

            selectedMesaId = idMesa;
            selectedMesaCapacidad = capacidad;
            selectedMesaNombre = nombreMesa;

            // Estado 1: Libre -> Abrir Modal Opciones (Ocupar o Reservar)
            if (estado == 1) {
                modalOpciones.show();
            } 
            // Estado 3: Reservada -> Permitir Ocupar directamente (confirmar llegada)
            else if (estado == 3) {
                abrirModalAsignar();
            }
            // Estado 2: Ocupada -> Abrir Modal Liberar
            else if (estado == 2) {
                document.getElementById('liberar_id_mesa').value = idMesa;
                document.getElementById('modalLiberarTitulo').innerText = 'Liberar ' + nombreMesa;
                
                modalLiberar.show();
            }
             // Estado 3: Reservada -> Podríamos añadir lógica para ocupar una reservada. Por ahora lo tratamos como Ocupada o Libre? 
             // Si estado es 3, el usuario debería poder ocuparla (llegaron los clientes) o cancelar.
             // Vamos a asumir que por ahora solo tratamos 1 y 2.
        }

        function abrirModalAsignar() {
            modalOpciones.hide();
            document.getElementById('asignar_id_mesa').value = selectedMesaId;
                
            const inputComensales = document.getElementById('num-comensales');
            inputComensales.max = selectedMesaCapacidad;
            document.getElementById('max-sillas').value = selectedMesaCapacidad;
            inputComensales.value = ''; // Reset
            
            document.getElementById('modalAsignarTitulo').innerText = 'Asignar ' + selectedMesaNombre + ' (Máx: ' + selectedMesaCapacidad + ')';
            
            modalAsignar.show();
        }

        function abrirModalReservar() {
            modalOpciones.hide();
            document.getElementById('reservar_id_mesa').value = selectedMesaId;
            document.getElementById('reserva_comensales').max = selectedMesaCapacidad;
            document.getElementById('modalReservarTitulo').innerHTML = '<i class="fa-solid fa-calendar-plus"></i> Reservar ' + selectedMesaNombre;
            
            // Set default dates (now and +1 hour)
            const now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            document.getElementById('fecha_inicio').value = now.toISOString().slice(0, 16);
            
            const later = new Date(now.getTime() + 60*60*1000); // +1 hour
            // no need to adujst timezone again if we use getTime from the adjusted 'now' object? No wait.
            // setMinutes modifies inplace. 
            // Let's just do it cleanly.
            
            // Re-calc for clean value
            const d1 = new Date();
            const d2 = new Date(d1.getTime() + 60*60*1000);
            
            const toLocalISO = (d) => {
                const off = d.getTimezoneOffset() * 60000;
                return new Date(d.getTime() - off).toISOString().slice(0, 16);
            };

            document.getElementById('fecha_inicio').value = toLocalISO(d1);
            document.getElementById('fecha_fin').value = toLocalISO(d2);

            modalReservar.show();
        }



        function modificarSillas(btn, delta) {
            const container = btn.closest('.capacidad-card');
            const input = container.querySelector('.input-sillas');
            const display = container.querySelector('.display-sillas');
            
            let current = parseInt(input.value);
            let newVal = current + delta;
            
            if (newVal < 1) newVal = 1; // Mínimo 1 silla
            if (newVal > 20) newVal = 20; // Máximo razonable
            
            input.value = newVal;
            display.innerText = newVal;
        }
    </script>
</body>
</html>