<?php
session_start();
require_once '../bbdd/conexion.php';

if (!isset($_SESSION['loginok'])) {
    header("Location: login.php");
    exit();
}

$id_mesa = $_GET['id_mesa'] ?? null;
$id_sala = $_GET['id_sala'] ?? null;

if (!$id_mesa || !$id_sala) {
    header("Location: ver_sala.php");
    exit();
}

// Obtener info mesa y ocupación actual
$stmt = $conn->prepare("
    SELECT m.*, u.username as camarero_nombre, o.inicio_ocupacion, o.num_comensales as comensales_actuales
    FROM mesas m
    LEFT JOIN users u ON m.asignado_por = u.id
    LEFT JOIN ocupaciones o ON o.id_mesa = m.id AND o.final_ocupacion IS NULL
    WHERE m.id = ?
");
$stmt->execute([$id_mesa]);
$mesa = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$mesa || $mesa['estado'] != 2) {
    // Si no está ocupada, no se puede liberar
    header("Location: ver_sala.php?id_sala=" . $id_sala . "&error=no_ocupada");
    exit();
}

// Obtener info sala
$stmt_sala = $conn->prepare("SELECT nombre FROM salas WHERE id = ?");
$stmt_sala->execute([$id_sala]);
$sala = $stmt_sala->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Liberar <?= htmlspecialchars($mesa['nombre']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/panel_principal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            flex-direction: column;
            height: 100vh;
        }
        .main-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .card-liberar {
            width: 100%;
            max-width: 500px;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            background: white;
            border-top: 5px solid #dc3545;
        }
    </style>
</head>
<body>

    <?php require_once '../header.php'; ?>

    <div class="main-container">
        <div class="card-liberar">
            <h2 class="text-center mb-4 text-danger"><i class="fa-solid fa-unlock"></i> Liberar Mesa</h2>
            
            <div class="mb-4">
                <h4 class="text-center"><?= htmlspecialchars($mesa['nombre']) ?></h4>
                <hr>
                <div class="row">
                    <div class="col-6 text-end fw-bold">Sala:</div>
                    <div class="col-6"><?= htmlspecialchars($sala['nombre']) ?></div>
                    
                    <div class="col-6 text-end fw-bold">Asignado por:</div>
                    <div class="col-6"><?= htmlspecialchars($mesa['camarero_nombre'] ?? 'Desconocido') ?></div>
                    
                    <div class="col-6 text-end fw-bold">Comensales:</div>
                    <div class="col-6"><?= $mesa['comensales_actuales'] ?? '?' ?></div>
                    
                    <div class="col-6 text-end fw-bold">Hora inicio:</div>
                    <div class="col-6"><?= $mesa['inicio_ocupacion'] ? date('H:i', strtotime($mesa['inicio_ocupacion'])) : '-' ?></div>
                </div>
            </div>

            <div class="alert alert-warning text-center">
                <i class="fa-solid fa-triangle-exclamation"></i> ¿Estás seguro de que quieres finalizar esta ocupación?
            </div>

            <form action="../proc/liberar_mesa.php" method="POST" id="liberar-mesa-form">
                <input type="hidden" name="id_mesa" value="<?= $id_mesa ?>">
                <input type="hidden" name="id_sala" value="<?= $id_sala ?>">
                <!-- Hidden inputs for JS validation -->
                <input type="hidden" id="camarero" value="<?= $mesa['camarero_nombre'] ?>">
                <input type="hidden" id="camarero_sesion" value="<?= $_SESSION['username'] ?>">

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-danger btn-lg" id="btn-liberar">
                        <i class="fa-solid fa-check"></i> Sí, Liberar Mesa
                    </button>
                    <a href="ver_sala.php?id_sala=<?= $id_sala ?>" class="btn btn-outline-secondary">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="../js/liberar_mesa.js"></script>
</body>
</html>
