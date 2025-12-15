<?php
session_start();
require_once '../bbdd/conexion.php';

// Validar sesión
if (!isset($_SESSION['loginok'])) {
    header("Location: ../view/login.php");
    exit();
}

// Validar Permisos (Camarero=1, Jefe=5)
$rol = $_SESSION['rol'];
if (!in_array($rol, [1, 5])) {
    die("Error: No tienes permisos para realizar reservas.");
}

// Obtener datos del formulario
$id_mesa = $_POST['id_mesa'] ?? null;
$id_sala = $_POST['id_sala'] ?? null;
$id_cliente = $_POST['id_cliente'] ?? null;
$fecha_inicio = $_POST['fecha_inicio'] ?? null;
$fecha_fin = $_POST['fecha_fin'] ?? null;
$num_comensales = $_POST['num_comensales'] ?? null;

// Validaciones básicas
if (!$id_mesa || !$id_sala || !$id_cliente || !$fecha_inicio || !$fecha_fin || !$num_comensales) {
    header("Location: ../view/ver_sala.php?id_sala=$id_sala&error=campos_vacios");
    exit();
}

try {
    // Validar duración (DB Constraint <= 7 horas)
    $inicio = new DateTime($fecha_inicio);
    $fin = new DateTime($fecha_fin);
    
    if ($fin <= $inicio) {
        throw new Exception("La fecha de fin debe ser posterior a la de inicio.");
    }
    
    $diff = $fin->diff($inicio);
    $horas = $diff->h + ($diff->days * 24);
    
    if ($horas > 7) {
        throw new Exception("La reserva no puede durar más de 7 horas.");
    }
    
    // Insertar Reserva
    // id_usuario_reserva es el cliente
    $sql = "INSERT INTO reservas (id_usuario_reserva, id_mesa, fecha_inicio, fecha_fin, num_comensales, estado) 
            VALUES (:cliente, :mesa, :inicio, :fin, :comensales, 2)"; // Estado 2 = Confirmada
            
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':cliente' => $id_cliente,
        ':mesa' => $id_mesa,
        ':inicio' => $fecha_inicio,
        ':fin' => $fecha_fin,
        ':comensales' => $num_comensales
    ]);
    
    // Opcional: Si la reserva es "ahora mismo" (dentro de los próximos 15 mins), podríamos cambiar el estado de la mesa a 3 (Reservada)
    // Pero la BBDD tiene un campo estado en mesas.
    // Vamos a dejar que el estado 'reservada' (3) se gestione manualmente o por otro script.
    // Sin embargo, para visualizarlo, si la reserva solapa con AHORA, deberíamos actualizar el estado.
    /*
    $now = new DateTime();
    if ($inicio <= $now && $fin >= $now) {
        $stmt_update = $conn->prepare("UPDATE mesas SET estado = 3 WHERE id = ?");
        $stmt_update->execute([$id_mesa]);
    }
    */

    // Check for redirect URL
    $redirect_url = $_POST['redirect_url'] ?? "../view/ver_sala.php?id_sala=$id_sala&msg=reserva_ok";
    
    // Append query params if using default (to keep existing behavior exact match logic)
    // Actually, let's just use the passed URL or construct the default.
    if (!isset($_POST['redirect_url'])) {
         header("Location: ../view/ver_sala.php?id_sala=$id_sala&msg=reserva_ok");
    } else {
         header("Location: $redirect_url" . (strpos($redirect_url, '?') !== false ? '&' : '?') . "msg=reserva_ok");
    }
    exit();

} catch (Exception $e) {
    // En caso de error (incluyendo constraint violation)
    $error_msg = urlencode($e->getMessage());
    $redirect_url = $_POST['redirect_url'] ?? "../view/ver_sala.php?id_sala=$id_sala";
    
    // If redirect_url provided, we append error there
    if (!isset($_POST['redirect_url'])) {
         header("Location: ../view/ver_sala.php?id_sala=$id_sala&error=$error_msg");
    } else {
         header("Location: $redirect_url" . (strpos($redirect_url, '?') !== false ? '&' : '?') . "error=$error_msg");
    }
    exit();
}
