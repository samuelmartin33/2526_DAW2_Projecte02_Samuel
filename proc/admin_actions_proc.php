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
        $username = trim($_POST['username'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $apellido = trim($_POST['apellido'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $rol = $_POST['rol'] ?? '';
        $password = $_POST['password'] ?? ''; 
        
        // Basic validation
        if (empty($username) || empty($nombre) || empty($email)) {
            header("Location: ../view/admin_panel.php?error=empty_fields");
            exit;
        }

        // Validate rol (1=Camarero, 4=Mantenimiento, 5=Jefe)
        $allowed_roles = ['1', '4', '5'];
        if (in_array($rol, $allowed_roles)) {
            try {
                if (!empty($password)) {
                    // Update WITH password
                    // Password must be hashed. Assuming password_hash() default (bcrypt)
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    
                    $sql = "UPDATE users SET username = :username, nombre = :nombre, apellido = :apellido, email = :email, rol = :rol, password_hash = :pass WHERE id = :id";
                    $stmt = $conn->prepare($sql);
                    $stmt->bindParam(':pass', $password_hash);
                } else {
                    // Update WITHOUT password
                    $sql = "UPDATE users SET username = :username, nombre = :nombre, apellido = :apellido, email = :email, rol = :rol WHERE id = :id";
                    $stmt = $conn->prepare($sql);
                }
                
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':nombre', $nombre);
                $stmt->bindParam(':apellido', $apellido);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':rol', $rol);
                $stmt->bindParam(':id', $id);
                
                $stmt->execute();
                
                header("Location: ../view/admin_panel.php?status=success_user");
                exit;
            } catch (PDOException $e) {
                // Check if duplicate entry (e.g. username/email)
                if ($e->errorInfo[1] == 1062) {
                     header("Location: ../view/admin_panel.php?error=duplicate_entry");
                } else {
                     header("Location: ../view/admin_panel.php?error=db_error");
                }
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
        
        $imagen_db = null; // Default null or a default image path?
        
        // Handle File Upload
        if (isset($_FILES['imagen_file']) && $_FILES['imagen_file']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['imagen_file']['tmp_name'];
            $fileName = $_FILES['imagen_file']['name'];
            $fileSize = $_FILES['imagen_file']['size'];
            $fileType = $_FILES['imagen_file']['type'];
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));
            
            $allowedfileExtensions = array('jpg', 'gif', 'png', 'jpeg', 'webp');
            if (in_array($fileExtension, $allowedfileExtensions)) {
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                $uploadFileDir = '../img/';
                $dest_path = $uploadFileDir . $newFileName;
                
                if(move_uploaded_file($fileTmpPath, $dest_path)) {
                    $imagen_db = 'img/' . $newFileName; 
                } else {
                     header("Location: ../view/admin_panel.php?error=upload_failed");
                     exit;
                }
            } else {
                header("Location: ../view/admin_panel.php?error=invalid_file_type");
                exit;
            }
        }

        if (!empty($nombre)) {
            try {
                // Insert name AND imagen
                $sql = "INSERT INTO salas (nombre, imagen) VALUES (:nombre, :imagen)";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':nombre', $nombre);
                $stmt->bindParam(':imagen', $imagen_db);
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
        
        // Handle File Upload
        $imagen_db = null;
        if (isset($_FILES['imagen_file']) && $_FILES['imagen_file']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['imagen_file']['tmp_name'];
            $fileName = $_FILES['imagen_file']['name'];
            $fileSize = $_FILES['imagen_file']['size'];
            $fileType = $_FILES['imagen_file']['type'];
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));
            
            $allowedfileExtensions = array('jpg', 'gif', 'png', 'jpeg', 'webp');
            if (in_array($fileExtension, $allowedfileExtensions)) {
                // Sanitize filename
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                $uploadFileDir = '../img/';
                $dest_path = $uploadFileDir . $newFileName;
                
                if(move_uploaded_file($fileTmpPath, $dest_path)) {
                    // Success, saved in ../img/, in DB we store img/filename
                    $imagen_db = 'img/' . $newFileName; 
                } else {
                     header("Location: ../view/admin_panel.php?error=upload_failed");
                     exit;
                }
            } else {
                header("Location: ../view/admin_panel.php?error=invalid_file_type");
                exit;
            }
        }

        if (!empty($nombre)) {
             try {
                if ($imagen_db) {
                     // Update Name AND Image
                     $sql = "UPDATE salas SET nombre = :nombre, imagen = :imagen WHERE id = :id";
                     $stmt = $conn->prepare($sql);
                     $stmt->bindParam(':imagen', $imagen_db);
                } else {
                     // Update only Name
                     $sql = "UPDATE salas SET nombre = :nombre WHERE id = :id";
                     $stmt = $conn->prepare($sql);
                }
                
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
