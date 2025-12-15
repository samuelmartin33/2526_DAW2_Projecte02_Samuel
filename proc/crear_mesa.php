<?php
session_start();
require_once __DIR__ . '/../BBDD/conexion.php';

if (!isset($_SESSION['loginok'])) {
    header("Location: ../view/login.php");
    exit();
}

if (isset($_GET['id_sala'])) {
    $id_sala = $_GET['id_sala'];
    
    // Crea una mesa por defecto en el centro (50%, 50%)
    // Estado 1 = Libre
    $stmt = $conn->prepare("INSERT INTO mesas (id_sala, nombre, sillas, estado, pos_x, pos_y) VALUES (?, 'Nueva', 4, 1, 50, 50)");
    $stmt->execute([$id_sala]);
    
    // Redirección a la vista de sala
    header("Location: ../view/ver_sala.php?id_sala=" . $id_sala);
    exit();
} else {
    // Si falta el ID, volver atrás
    header("Location: ../view/index.php");
    exit();
}
?>
