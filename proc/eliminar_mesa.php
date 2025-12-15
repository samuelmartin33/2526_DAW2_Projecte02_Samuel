<?php
session_start();
require_once __DIR__ . '/../BBDD/conexion.php';

if (!isset($_SESSION['loginok'])) {
    header("Location: ../view/login.php");
    exit();
}

if (isset($_GET['id_mesa']) && isset($_GET['id_sala'])) {
    // Borra la mesa
    $stmt = $conn->prepare("DELETE FROM mesas WHERE id = ?");
    $stmt->execute([$_GET['id_mesa']]);
    
    // Redirección a la vista de sala (usando el ID de sala que recibimos para saber dónde volver)
    header("Location: ../view/ver_sala.php?id_sala=" . $_GET['id_sala']);
    exit();
} else {
    header("Location: ../view/index.php");
    exit();
}
?>
