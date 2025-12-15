<?php
session_start();
require_once '../BBDD/conexion.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] != 2) {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? '';

    if ($action === 'edit_user' && !empty($id)) {
        $rol = $_POST['rol'] ?? '';
        
        // Validate rol (1=Camarero, 4=Mantenimiento, 5=Jefe)
        $allowed_roles = ['1', '4', '5'];
        if (in_array($rol, $allowed_roles)) {
            try {
                $sql = "UPDATE users SET rol = :rol WHERE id = :id";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':rol', $rol);
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                
                header("Location: ../view/admin_panel.php?status=success_user");
                exit;
            } catch (PDOException $e) {
                header("Location: ../view/admin_panel.php?error=db_error");
                exit;
            }
        } else {
             header("Location: ../view/admin_panel.php?error=invalid_role");
             exit;
        }

    } elseif ($action === 'toggle_user_status' && !empty($id)) {
        // Fetch current status
        $stmt = $conn->prepare("SELECT fecha_baja FROM users WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if (empty($user['fecha_baja'])) {
                // Deactivate: Set fecha_baja to NOW()
                $sql = "UPDATE users SET fecha_baja = NOW() WHERE id = :id";
            } else {
                // Reactivate: Set fecha_baja to NULL
                $sql = "UPDATE users SET fecha_baja = NULL WHERE id = :id";
            }
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            header("Location: ../view/admin_panel.php?status=success_status");
            exit;
        } else {
             header("Location: ../view/admin_panel.php?error=user_not_found");
             exit;
        }

    } elseif ($action === 'create_sala') {
        $nombre = trim($_POST['nombre'] ?? '');
        if (!empty($nombre)) {
            try {
                $sql = "INSERT INTO salas (nombre) VALUES (:nombre)";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':nombre', $nombre);
                $stmt->execute();
                
                header("Location: ../view/admin_panel.php?status=success_create_sala");
                exit;
            } catch (PDOException $e) {
                header("Location: ../view/admin_panel.php?error=db_error");
                exit;
            }
        } else {
             header("Location: ../view/admin_panel.php?error=invalid_data");
             exit;
        }

    } elseif ($action === 'delete_sala' && !empty($id)) {
        try {
            // Note: DB is configured with ON DELETE CASCADE for tables referencing users/salas if set correctly.
            // But for 'salas', tables referencing it are 'mesas'.
            // In bbdd.sql: FOREIGN KEY (id_sala) REFERENCES salas(id) ON DELETE CASCADE
            // So we can just delete query.
            $sql = "DELETE FROM salas WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            header("Location: ../view/admin_panel.php?status=success_delete_sala");
            exit;
        } catch (PDOException $e) {
            header("Location: ../view/admin_panel.php?error=db_error_fk"); // Likely foreign key constraint if cascade not set
            exit;
        }

    } elseif ($action === 'edit_sala' && !empty($id)) {
        $nombre = trim($_POST['nombre'] ?? '');

        if (!empty($nombre)) {
             try {
                $sql = "UPDATE salas SET nombre = :nombre WHERE id = :id";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':nombre', $nombre);
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                
                header("Location: ../view/admin_panel.php?status=success_sala");
                exit;
            } catch (PDOException $e) {
                header("Location: ../view/admin_panel.php?error=db_error");
                exit;
            }
        } else {
             header("Location: ../view/admin_panel.php?error=invalid_data");
             exit;
        }
    }
}

// Redirect if no valid action
header("Location: ../view/admin_panel.php");
exit;
?>
