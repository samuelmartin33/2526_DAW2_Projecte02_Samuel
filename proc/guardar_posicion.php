<?php
session_start();

// 1. Conexión usando ruta absoluta (__DIR__ es la carpeta 'proc')
require_once __DIR__ . '/../BBDD/conexion.php';

// 2. Seguridad: Si no está logueado, va a la carpeta 'view'
if (!isset($_SESSION['loginok']) || $_SESSION['loginok'] !== true) {
    header("Location: ../view/login.php");
    exit();
}

// 3. Proceso de guardado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_sala'])) {
    $id_sala = $_POST['id_sala'];

    try {
        $conn->beginTransaction();

        // ACTUALIZAR MESAS EXISTENTES (si las hay)
        if (isset($_POST['posiciones']) && is_array($_POST['posiciones'])) {
            $stmt = $conn->prepare("UPDATE mesas SET pos_x = :x, pos_y = :y, sillas = :sillas WHERE id = :id");

            foreach ($_POST['posiciones'] as $id_mesa => $coord) {
                 $stmt->execute([
                    ':x' => $coord['x'],
                    ':y' => $coord['y'],
                    ':sillas' => $coord['sillas'] ?? 4,
                    ':id' => $id_mesa
                ]);
            }
        }

        // GUARDAR NUEVAS MESAS
        if (isset($_POST['nuevas_mesas']) && is_array($_POST['nuevas_mesas'])) {
            $stmt_insert = $conn->prepare("INSERT INTO mesas (id_sala, nombre, sillas, estado, pos_x, pos_y) VALUES (?, ?, ?, 1, ?, ?)");
            foreach ($_POST['nuevas_mesas'] as $nueva) {
                $stmt_insert->execute([
                    $id_sala,
                    $nueva['nombre'], // Nombre personalizable
                    $nueva['sillas'] ?? 4,
                    $nueva['x'],
                    $nueva['y']
                ]);
            }
        }

        $conn->commit();
        
        // 4. Redirección correcta a la vista
        header("Location: ../view/ver_sala.php?id_sala=" . $id_sala . "&status=guardado");
        exit();

    } catch (Exception $e) {
        $conn->rollBack();
        die("Error al guardar: " . $e->getMessage());
    }
} else {
    // Si intentan entrar directo sin enviar datos, volver al index
    header("Location: ../index.php"); // Updated path to index.php (it is in root usually, but check location)
    // view/index.php doesn't exist? Original file had ../view/index.php but index.php is at root usually c:\wamp64\www\DAW2\2526_DAW2_Projecte02_Samuel\index.php.
    // proc is in proc/. ../index.php is correct.
    exit();
}
?>