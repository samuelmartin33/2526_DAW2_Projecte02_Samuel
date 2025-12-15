<?php
session_start();
require_once __DIR__ . '/../BBDD/conexion.php';

if (!isset($_SESSION['loginok'])) {
    header("Location: ../view/login.php");
    exit();
}

if (isset($_GET['id_mesa']) && isset($_GET['id_sala'])) {
    try {
        $conn->beginTransaction();

        // 1. Borrar ocupaciones asociadas
        $stmt1 = $conn->prepare("DELETE FROM ocupaciones WHERE id_mesa = ?");
        $stmt1->execute([$_GET['id_mesa']]);

        // 2. Borrar reservas asociadas
        $stmt2 = $conn->prepare("DELETE FROM reservas WHERE id_mesa = ?");
        $stmt2->execute([$_GET['id_mesa']]);
        
        // 3. Borrar la mesa
        $stmt = $conn->prepare("DELETE FROM mesas WHERE id = ?");
        $stmt->execute([$_GET['id_mesa']]);

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollBack();
        die("Error al eliminar la mesa: " . $e->getMessage());
    }
    
    // Redirección a la vista de sala (usando el ID de sala que recibimos para saber dónde volver)
    header("Location: ../view/ver_sala.php?id_sala=" . $_GET['id_sala']);
    exit();
} else {
    header("Location: ../view/index.php");
    exit();
}
?>
