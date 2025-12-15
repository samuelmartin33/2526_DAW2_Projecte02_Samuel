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
    
    // Obtener info mesa para verificar permisos
    $stmt = $conn->prepare("SELECT asignado_por FROM mesas WHERE id = ?");
    $stmt->execute([$id_mesa]);
    $mesa = $stmt->fetch(PDO::FETCH_ASSOC);

    // Opcional: Verificar si el usuario actual es quien la asignó o es admin.
    // De momento lo dejamos libre para cualquier camarero (simplificado Projecte02)

    try {
        $conn->beginTransaction();

        // 1. Marcar ocupación como finalizada
        // Buscamos la ocupación activa de esta mesa
        $stmt_upd_ocu = $conn->prepare("UPDATE ocupaciones SET final_ocupacion = NOW() WHERE id_mesa = ? AND final_ocupacion IS NULL");
        $stmt_upd_ocu->execute([$id_mesa]);

        // 2. Liberar mesa
        $stmt_mesa = $conn->prepare("UPDATE mesas SET estado = 1, asignado_por = NULL WHERE id = ?");
        $stmt_mesa->execute([$id_mesa]);

        $conn->commit();
        
        header("Location: ../view/ver_sala.php?id_sala=" . $id_sala . "&status=liberada");
        exit();

    } catch (Exception $e) {
        $conn->rollBack();
        die("Error al liberar mesa: " . $e->getMessage());
    }
} else {
    header("Location: ../view/index.php");
}
?>
