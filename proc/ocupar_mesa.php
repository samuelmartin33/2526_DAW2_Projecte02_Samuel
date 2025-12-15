<?php
session_start();
require_once '../bbdd/conexion.php';

if (!isset($_SESSION['loginok'])) {
    header("Location: ../view/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_mesa = $_POST['id_mesa'];
    $id_sala = $_POST['id_sala'];
    $num_comensales = $_POST['num_comensales'];
    $id_camarero = $_SESSION['id_user'] ?? 0; // Asumiendo que guardamos id en login, si no buscarlo

    // Si id_user no está en sesión, lo buscamos por username
    if ($id_camarero == 0) {
        $stmt_u = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt_u->execute([$_SESSION['username']]);
        $id_camarero = $stmt_u->fetchColumn();
    }

    try {
        $conn->beginTransaction();

        // 1. Actualizar estado mesa
        $stmt_mesa = $conn->prepare("UPDATE mesas SET estado = 2, asignado_por = ? WHERE id = ?");
        $stmt_mesa->execute([$id_camarero, $id_mesa]);

        // 2. Crear registro ocupación
        $stmt_ocu = $conn->prepare("INSERT INTO ocupaciones (id_camarero, id_mesa, inicio_ocupacion, num_comensales) VALUES (?, ?, NOW(), ?)");
        $stmt_ocu->execute([$id_camarero, $id_mesa, $num_comensales]);

        $conn->commit();
        
        header("Location: ../view/ver_sala.php?id_sala=" . $id_sala . "&status=ocupada");
        exit();

    } catch (Exception $e) {
        $conn->rollBack();
        die("Error al ocupar mesa: " . $e->getMessage());
    }
} else {
    header("Location: ../view/index.php");
}
?>
